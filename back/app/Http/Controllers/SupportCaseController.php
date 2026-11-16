<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Models\Order;
use App\Models\SupportCase;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupportCaseController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => 'nullable|string|max:160',
            'status' => 'nullable|in:pending,open,resolved',
            'branch' => 'nullable|integer|min:0',
        ]);

        $query = SupportCase::visibleTo($request->user())
            ->with(['agencia:id,nombre', 'order:id,order_number,total', 'lastMessage']);
        if (isset($data['branch']) && $this->isAdmin($request)) {
            $query->where('agencia_id', $data['branch'] ?: null);
        }
        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }
        if (!empty($data['search'])) {
            $pattern = '%' . $data['search'] . '%';
            $query->where(function ($query) use ($pattern) {
                $query->where('customer_name', 'like', $pattern)
                    ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', $pattern));
            });
        }

        return $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(25)
            ->through(fn (SupportCase $case) => $this->present($case));
    }

    public function branches(Request $request)
    {
        abort_unless($this->isAdmin($request), 403);

        return Agencia::where('status', 'ACTIVO')->orderBy('nombre')->get(['id', 'nombre']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_number' => 'nullable|string|max:120',
            'customer_name' => 'required_without:order_number|nullable|string|max:160',
            'message' => 'nullable|string|max:2000',
        ]);
        $user = $request->user();

        $case = DB::transaction(function () use ($data, $request, $user) {
            if (!empty($data['order_number'])) {
                $order = Order::where('order_number', $data['order_number'])
                    ->whereIn('source', ['web', 'app'])->lockForUpdate()->firstOrFail();
                $branch = Agencia::whereKey((int) ($order->meta['sucursal_id'] ?? 0))->value('id');
                abort_unless($this->isAdmin($request) || ($branch && (int) $branch === (int) $user->agencia_id), 404);

                return SupportCase::firstOrCreate(['order_id' => $order->id], [
                    'agencia_id' => $branch,
                    'customer_name' => mb_substr($order->customer_name ?: 'Cliente del pedido', 0, 160),
                    'source' => $order->source,
                ]);
            }

            abort_unless($this->isAdmin($request), 403);
            $case = SupportCase::create([
                'customer_name' => $data['customer_name'],
                'source' => 'direct',
            ]);
            if (!empty($data['message'])) {
                $case->messages()->create(['author' => 'customer', 'text' => $data['message'], 'user_id' => $user->id]);
            }

            return $case;
        });

        return $this->show($request, $case->id);
    }

    public function show(Request $request, int $id)
    {
        $data = $request->validate(['before' => 'nullable|integer|min:1']);
        $case = SupportCase::visibleTo($request->user())
            ->with(['agencia:id,nombre', 'order.items', 'lastMessage'])->findOrFail($id);
        $messages = $case->messages()->when(isset($data['before']), fn ($query) => $query->where('id', '<', $data['before']))
            ->orderByDesc('id')->limit(51)->get();
        $more = $messages->count() > 50;
        $messages = $messages->take(50)->reverse()->values();

        return $this->present($case) + [
            'messages' => $messages->map(fn (SupportMessage $message) => $this->message($message)),
            'before' => $more ? $messages->first()->id : null,
        ];
    }

    public function addMessage(Request $request, int $id)
    {
        $data = $request->validate(['author' => 'required|in:note,agent', 'text' => 'required|string|max:2000']);
        $message = DB::transaction(function () use ($request, $id, $data) {
            $case = $this->lockedCase($request, $id);
            abort_if($case->status === 'resolved', 422, 'Reabre el caso antes de agregar un mensaje.');
            abort_if($data['author'] === 'agent' && ($case->mode !== 'manual' || !$case->agencia_id), 422,
                'Asigna una sucursal y activa la atención manual para guardar una respuesta.');
            if ($case->status === 'pending' && $data['author'] === 'agent') {
                $case->status = 'open';
            }
            $case->touch();

            return $case->messages()->create($data + ['user_id' => $request->user()->id]);
        });

        return response()->json($this->message($message), 201);
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate(['action' => 'required|in:manual,bot,resolve,reopen']);
        DB::transaction(function () use ($request, $id, $data) {
            $case = $this->lockedCase($request, $id);
            $action = $data['action'];
            if ($action === 'bot') {
                abort_if(!$case->agencia_id || $case->status === 'resolved', 422, 'Asigna una sucursal y abre el caso primero.');
            }
            if ($action === 'manual') {
                abort_if($case->status === 'resolved', 422, 'Reabre el caso primero.');
            }
            if ($action === 'reopen' && $case->status !== 'resolved') {
                return;
            }
            $case->mode = $action === 'bot' ? 'bot' : 'manual';
            if ($action === 'resolve') {
                $case->status = 'resolved';
            } elseif ($case->agencia_id) {
                $case->status = 'open';
            } else {
                $case->status = 'pending';
            }
            if ($case->isDirty()) {
                $case->save();
                $labels = [
                    'manual' => 'Atención manual activada.',
                    'bot' => 'Modo bot guardado. Las respuestas automáticas están pendientes.',
                    'resolve' => 'Atención resuelta.',
                    'reopen' => 'Atención reabierta en modo manual.',
                ];
                $case->messages()->create(['user_id' => $request->user()->id, 'author' => 'system', 'text' => $labels[$action]]);
            }
        });

        return $this->show($request, $id);
    }

    public function assign(Request $request, int $id)
    {
        abort_unless($this->isAdmin($request), 403);
        $data = $request->validate([
            'agencia_id' => ['required', 'integer', Rule::exists('agencias', 'id')->where('status', 'ACTIVO')],
        ]);
        DB::transaction(function () use ($request, $id, $data) {
            $case = $this->lockedCase($request, $id);
            if ((int) $case->agencia_id === (int) $data['agencia_id']) {
                return;
            }
            $case->update(['agencia_id' => $data['agencia_id'], 'mode' => 'manual']);
            $case->messages()->create([
                'user_id' => $request->user()->id,
                'author' => 'system',
                'text' => 'Sucursal de atención actualizada.',
            ]);
        });

        return $this->show($request, $id);
    }

    private function lockedCase(Request $request, int $id): SupportCase
    {
        return SupportCase::visibleTo($request->user())->lockForUpdate()->findOrFail($id);
    }

    private function isAdmin(Request $request): bool
    {
        return (int) $request->user()->id === 1;
    }

    private function present(SupportCase $case): array
    {
        $order = $case->order;
        return [
            'id' => $case->id,
            'name' => $case->customer_name,
            'initials' => collect(preg_split('/\s+/u', $case->customer_name, -1, PREG_SPLIT_NO_EMPTY))
                ->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode(''),
            'branch_id' => $case->agencia_id,
            'branch' => $case->agencia?->nombre ?? 'Sin asignar',
            'source' => $case->source,
            'status' => $case->status,
            'mode' => $case->mode,
            'at' => $case->updated_at->toISOString(),
            'preview' => $case->lastMessage ? $this->message($case->lastMessage) : null,
            'order' => $order ? [
                'number' => $order->order_number,
                'total' => $order->total,
                'items' => $order->relationLoaded('items')
                    ? $order->items->map(fn ($item) => ['id' => $item->id, 'text' => "{$item->quantity} × {$item->name} · Bs {$item->subtotal}"])
                    : [],
            ] : null,
        ];
    }

    private function message(SupportMessage $message): array
    {
        return ['id' => $message->id, 'author' => $message->author, 'text' => $message->text, 'at' => $message->created_at->toISOString()];
    }
}

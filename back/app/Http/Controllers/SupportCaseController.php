<?php

namespace App\Http\Controllers;

use App\Models\Agencia;
use App\Models\Order;
use App\Models\SupportCase;
use App\Models\SupportMessage;
use App\Services\SupportRealtime;
use App\Services\WhatsAppTestReplies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SupportCaseController extends Controller
{
    public function realtimeTicket(Request $request, SupportRealtime $realtime)
    {
        return response()->json(['ticket' => $realtime->ticket($request->user())])
            ->header('Cache-Control', 'private, no-store');
    }

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
        $messages->where('author', 'agent')->load('operator:id,name');

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

        return response()->json($this->message($message, true), 201);
    }

    public function sendWhatsAppReply(Request $request, int $id, WhatsAppTestReplies $whatsapp, SupportRealtime $realtime)
    {
        $data = $request->validate(['text' => 'required|string|max:2000', 'request_id' => 'required|uuid']);
        [$reply, $recipient] = DB::transaction(function () use ($request, $id, $data, $whatsapp) {
            $case = $this->lockedCase($request, $id);
            abort_unless($case->source === 'whatsapp', 422, 'Esta conversación no pertenece a WhatsApp.');
            $existing = $case->messages()->where('whatsapp_send_key', $data['request_id'])->first();
            if ($existing) {
                abort_unless($existing->text === $data['text'], 409, 'Esta solicitud ya se usó para otro texto.');
                return [$existing, null];
            }

            abort_unless($case->agencia_id && $case->mode === 'manual' && $case->status !== 'resolved', 422,
                'Asigna la conversación a una sucursal y abre la atención manual para responder.');
            abort_unless($whatsapp->isConfigured(), 503, 'Falta configurar el token de envío de WhatsApp de prueba.');
            abort_unless($case->whatsapp_phone_id === $whatsapp->phoneId() && $case->whatsapp_recipient &&
                $case->whatsapp_last_inbound > now()->timestamp - 86400, 422,
                'Pide un mensaje nuevo al número de prueba para abrir la ventana de respuesta de WhatsApp.');

            $reply = $case->messages()->create([
                'author' => 'agent',
                'text' => $data['text'],
                'user_id' => $request->user()->id,
                'whatsapp_send_key' => $data['request_id'],
                'whatsapp_state' => 'sending',
            ]);
            $case->touch();

            return [$reply, $case->whatsapp_recipient];
        }, 3);

        if ($recipient === null) {
            return $this->message($reply, true);
        }

        [$state, $messageId, $diagnostic] = $whatsapp->send($recipient, $reply->text);
        $branch = DB::transaction(function () use ($id, $reply, $state, $messageId) {
            $case = SupportCase::lockForUpdate()->findOrFail($id);
            $reply->whatsapp_state = $state;
            $reply->whatsapp_message_id = $messageId;
            $reply->save();
            if ($state === 'accepted' && $case->status === 'pending') {
                $case->status = 'open';
            }
            $case->touch();

            return $case->agencia_id === null ? null : (int) $case->agencia_id;
        }, 3);
        if ($diagnostic !== null) {
            Log::warning('Respuesta inesperada al enviar por WhatsApp', [
                'case_id' => $id,
                'reply_id' => $reply->id,
                'state' => $state,
            ] + $diagnostic);
        }
        $realtime->notify([['id' => $id, 'branch' => $branch]]);

        $result = $this->message($reply, true);
        if ($state === 'failed') {
            $code = $diagnostic['code'];
            $hint = match ($code) {
                190 => 'Meta rechazó el token de acceso. Revisa si sigue vigente y pertenece a la app de prueba.',
                10, 200, 131005 => 'Meta rechazó los permisos de envío. Revisa whatsapp_business_messaging para el número de prueba.',
                131047 => 'La ventana de respuesta se cerró. Envía un mensaje nuevo desde el celular al número de prueba.',
                default => 'Meta rechazó el envío. Revisa la configuración de WhatsApp de prueba.',
            };
            $result['whatsapp_error'] = $code === null ? $hint : $hint . " Código de Meta $code.";
        }

        return response()->json($result, 201);
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
            if ($case->isDirty('mode') && $case->source === 'whatsapp') {
                $case->whatsapp_bot_step = null;
                $case->whatsapp_context = null;
                $case->whatsapp_context_expires_at = null;
                $case->whatsapp_indicator = $action === 'bot' ? 'automatic' : null;
                $case->whatsapp_handoff_reason = null;
            }
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
                    'bot' => $this->botEnabled($case)
                        ? 'Bot activado para los próximos mensajes.'
                        : 'Modo bot guardado. Las respuestas automáticas están pendientes.',
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

    public function destroy(Request $request, int $id, SupportRealtime $realtime)
    {
        abort_unless($this->isAdmin($request), 403);

        $branch = DB::transaction(function () use ($request, $id) {
            $case = $this->lockedCase($request, $id);
            $branch = $case->agencia_id;
            $case->delete();

            return $branch === null ? null : (int) $branch;
        }, 3);
        $realtime->notify([['id' => $id, 'branch' => $branch]]);

        return response()->noContent();
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
            'initials' => $this->initials($case->customer_name),
            'branch_id' => $case->agencia_id,
            'branch' => $case->agencia?->nombre ?? 'Sin asignar',
            'source' => $case->source,
            'status' => $case->status,
            'mode' => $case->mode,
            'bot_enabled' => $this->botEnabled($case),
            'bot_indicator' => $case->whatsapp_indicator,
            'bot_handoff_reason' => $case->whatsapp_handoff_reason,
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

    private function botEnabled(SupportCase $case): bool
    {
        return $case->source === 'whatsapp' && app(\App\Services\WhatsAppSupportBot::class)->enabled();
    }

    private function initials(string $name): string
    {
        return collect(preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY))
            ->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
    }

    private function message(SupportMessage $message, bool $loadOperator = false): array
    {
        $operator = null;
        if ($message->author === 'agent') {
            if ($loadOperator) {
                $message->loadMissing('operator:id,name');
            }
            $operator = $message->relationLoaded('operator') ? $message->operator : null;
        }

        return [
            'id' => $message->id,
            'author' => $message->author,
            'author_initials' => $operator?->name ? $this->initials($operator->name) : null,
            'text' => $message->text,
            'at' => $message->created_at->toISOString(),
            'whatsapp_state' => $message->whatsapp_state,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Anulacion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
class AnulacionController extends Controller{
    public function pdf(Anulacion $anulacion){
        $anulacion->load(['user', 'userAutoriza', 'userAnulacion', 'sale']);

        // Código (prioriza número de factura si existe)
        $codigo = $anulacion->sale_id ?? $anulacion->id;

        // Monto formateado
        $monto = number_format((float)($anulacion->monto ?? 0), 2, '.', '');

        // Motivos marcados a partir del texto guardado en 'motivo'
        $motivo = (string)($anulacion->motivo ?? '');
        $has = fn($needle) => Str::of($motivo)->lower()->contains(Str::lower($needle));

        $checks = [
            'cajero' => $has('Error de cajero'),
            'cliente' => $has('Error de cliente'),
            'sistema' => $has('Error de sistema'),
            'ventaDuplicada' => $has('Venta duplicada'),
        ];

        // Datos para la vista
        $data = [
            'anulacion' => $anulacion,
            'codigo' => $codigo,
            'monto' => $monto,
            'checks' => $checks,
        ];

        // A5 horizontal suele parecerse al papel del ejemplo; ajusta si quieres A4
        $pdf = Pdf::loadView('anulaciones.pdf', $data)->setPaper('a5', 'landscape');

        return $pdf->stream("form-anulacion-{$anulacion->id}.pdf");
        // Si prefieres descargar:
        // return $pdf->download("form-anulacion-{$anulacion->id}.pdf");
    }

    public function index(Request $request)
    {
        $estado = $request->query('estado'); // Pendiente|Autorizado|Anulado|Rechazado
        $search = trim((string)$request->query('search', ''));
        $fi = $request->query('fi'); // YYYY-MM-DD
        $ff = $request->query('ff'); // YYYY-MM-DD
        $perPage = (int)$request->query('per_page', 25);

        $q = Anulacion::with(['user', 'userAutoriza', 'userAnulacion', 'sale'])
            ->when($estado, fn($qq) => $qq->where('estado', $estado))
            ->when($fi, fn($qq) => $qq->whereDate('fecha', '>=', $fi))
            ->when($ff, fn($qq) => $qq->whereDate('fecha', '<=', $ff))
            ->when($search, function ($qq) use ($search) {
                $qq->where(function ($w) use ($search) {
                    $w->where('cajero', 'like', "%{$search}%")
                        ->orWhere('motivo', 'like', "%{$search}%")
                        ->orWhere('seccion', 'like', "%{$search}%")
                        ->orWhere('detalle', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id');

        if ($perPage === 0) {
            return $q->get();
        }
        return $q->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
//            'fecha'   => ['required','date'],
//            'cajero'  => ['required','string','max:255'],
//            'monto'   => ['required','numeric','min:0'],
            'motivo' => ['nullable', 'string', 'max:255'],
            'sale_id' => ['nullable', 'integer', 'exists:sales,id'],
            'seccion' => ['nullable', 'string', 'max:255'],
            'detalle' => ['nullable', 'string', 'max:255'],
        ]);
//        si ya ecite la venta no permite crear ota vez
        if (isset($data['sale_id']) && Anulacion::where('sale_id', $data['sale_id'])->whereIn('estado', ['Pendiente', 'Autorizado'])->exists()) {
            return response()->json(['message' => 'Ya existe una solicitud de anulación pendiente o autorizada para esta venta.'], 422);
        }

        $data['user_id'] = $request->user()->id;
        $data['estado'] = 'Pendiente';
        $data['fecha'] = now();
        $data['cajero'] = $request->user()->name;
        $data['monto'] = $data['sale_id'] ? optional(\App\Models\Sale::find($data['sale_id']))->montoTotal : 0;

        $anulacion = Anulacion::create($data);
        return response()->json($anulacion->load(['user', 'userAutoriza', 'userAnulacion', 'sale']), 201);
    }

    public function autorizar(Request $request, Anulacion $anulacion)
    {
        //validar password de usurio
        $request->validate([
            'password' => ['required', 'string'],
        ]);
        $user = User::find($request->user()->id);
        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json(['message' => 'Contraseña incorrecta.'], 422);
        }
        if ($anulacion->estado !== 'Pendiente') {
            return response()->json(['message' => 'Solo se pueden autorizar registros en estado Pendiente'], 422);
        }
//        if ($request->user()->id === $anulacion->user_id) {
//            return response()->json(['message' => 'Quien solicita no puede autorizar su propia anulación'], 422);
//        }

        $anulacion->update([
            'estado' => 'Autorizado',
            'user_autoriza_id' => $request->user()->id            
        ]);

        return $anulacion->load(['user', 'userAutoriza', 'userAnulacion', 'sale']);
    }

    public function anular(Request $request, Anulacion $anulacion)
    {
        if ($anulacion->estado !== 'Autorizado') {
            return response()->json(['message' => 'Solo se pueden anular registros en estado Autorizado'], 422);
        }
//        if ($request->user()->id === $anulacion->user_autoriza_id) {
//            return response()->json(['message' => 'La misma persona que autorizó no puede ejecutar la anulación'], 422);
//        }

        $request->validate([
            'detalle' => ['nullable', 'string', 'max:255'],
        ]);

        $anulacion->update([
            'estado' => 'Anulado',
            'user_anulacion_id' => $request->user()->id,
            'detalle' => $anulacion->detalle . ' ' . $request->input('detalle', ''),
        ]);

        // Aquí podrías ejecutar la lógica contable: reversar sale_id, etc.
        return $anulacion->load(['user', 'userAutoriza', 'userAnulacion', 'sale']);
    }

    public function rechazar(Request $request, Anulacion $anulacion)
    {
        if ($anulacion->estado !== 'Pendiente') {
            return response()->json(['message' => 'Solo se pueden rechazar registros en estado Pendiente'], 422);
        }
//        if ($request->user()->id === $anulacion->user_id) {
//            return response()->json(['message' => 'Quien solicita no puede rechazar su propia anulación'], 422);
//        }

        $request->validate([
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $anulacion->update([
            'estado' => 'Rechazado',
            'user_autoriza_id' => $request->user()->id, // quien rechaza queda como “autoriza” (decisor)
            'motivo' => $anulacion->motivo . ' ' . $request->input('motivo', ''),
        ]);

        return $anulacion->load(['user', 'userAutoriza', 'userAnulacion', 'sale']);
    }
}

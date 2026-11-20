<?php

namespace App\Services;

use App\Models\Agencia;
use App\Models\SupportCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WhatsAppSupportBot
{
    private const MENU = "Responde con el número de tu opción:\n1. Horarios y direcciones\n2. Consultar un producto\n3. Hacer un pedido\n4. Hablar con una persona";
    private const HANDOFF = 'Tu mensaje quedó en la bandeja de atención. Una persona de nuestro equipo continuará la conversación.';

    public function __construct(private WhatsAppTestReplies $whatsapp, private SupportRealtime $realtime)
    {
    }

    public function enabled(): bool
    {
        return config('services.whatsapp_webhook.bot_enabled', false) && $this->whatsapp->isConfigured();
    }

    public function respond(int $caseId, int $inboundId): void
    {
        $updated = DB::transaction(function () use ($caseId, $inboundId) {
            $case = SupportCase::lockForUpdate()->find($caseId);
            if (!$this->enabled() || !$case || $case->mode !== 'bot' || $case->status === 'resolved' ||
                $case->whatsapp_phone_id !== $this->whatsapp->phoneId() || !$case->whatsapp_recipient ||
                $case->whatsapp_last_inbound <= now()->timestamp - 86400) {
                return null;
            }

            $inbound = $case->messages()->where('author', 'customer')->latest('id')->first();
            if ($inbound?->id !== $inboundId || $case->messages()->where('author', 'bot')->where('id', '>', $inboundId)->exists()) {
                return null;
            }

            [$text, $step] = $this->nextReply($case->whatsapp_bot_step, $inbound->text);
            // El bloqueo ordena el envío con los cambios a atención manual. No se reintenta la transacción después del envío.
            [$state, $messageId] = $this->whatsapp->send($case->whatsapp_recipient, $text);
            $reply = $case->messages()->make(['author' => 'bot', 'text' => $text, 'whatsapp_state' => $state]);
            $reply->whatsapp_message_id = $messageId;
            $reply->save();

            if ($step === null) {
                $case->mode = 'manual';
                $case->whatsapp_bot_step = null;
            } elseif ($state !== 'failed') {
                $case->whatsapp_bot_step = $step;
            }
            $case->touch();

            return ['id' => $caseId, 'branch' => $case->agencia_id === null ? null : (int) $case->agencia_id];
        });

        if ($updated) {
            $this->realtime->notify([$updated]);
        }
    }

    private function nextReply(?string $step, string $message): array
    {
        $option = Str::lower(Str::ascii(trim($message)));
        if (in_array($option, ['persona', 'asesor', 'humano'], true)) {
            return [self::HANDOFF, null];
        }
        if ($step === null) {
            return ["Hola, somos Farmacias Santidad Divina.\n\n" . self::MENU, 'menu'];
        }
        if ($option === '0' || $option === 'menu') {
            return [self::MENU, 'menu'];
        }

        return match ($step) {
            'product', 'order' => [self::HANDOFF, null],
            'branch' => $this->branchDetails($option),
            default => match ($option) {
                '1' => $this->branchList(),
                '2' => ["Indícanos el nombre del producto, su presentación, la cantidad y la sucursal que prefieres.\n\nEscribe 0 para volver al menú.", 'product'],
                '3' => ["Escribe los productos, las cantidades y la sucursal que prefieres. El equipo revisará tu solicitud contigo.\n\nEscribe 0 para volver al menú.", 'order'],
                '4' => [self::HANDOFF, null],
                default => [self::MENU, 'menu'],
            },
        };
    }

    private function branchList(): array
    {
        $branches = Agencia::where('status', 'ACTIVO')->orderBy('nombre')->orderBy('id')->get(['id', 'nombre']);
        if ($branches->isEmpty()) {
            return ["Por el momento no tenemos sucursales disponibles en el menú.\n\n" . self::MENU, 'menu'];
        }

        $options = $branches->map(fn (Agencia $branch) => "$branch->id. $branch->nombre")->implode("\n");

        return ["Elige una sucursal por su número:\n$options\n\nEscribe 0 para volver al menú.", 'branch'];
    }

    private function branchDetails(string $option): array
    {
        $branch = preg_match('/\A[1-9][0-9]{0,17}\z/', $option)
            ? Agencia::where('status', 'ACTIVO')->find($option)
            : null;
        if (!$branch) {
            return $this->branchList();
        }

        $schedule = implode(' · ', array_filter([trim((string) $branch->atencion), trim((string) $branch->horario)]));
        $address = trim((string) $branch->direccion);
        $map = trim((string) $branch->gps);
        if (!filter_var($map, FILTER_VALIDATE_URL) || parse_url($map, PHP_URL_SCHEME) !== 'https') {
            $map = is_numeric($branch->latitud) && is_numeric($branch->longitud)
                && abs((float) $branch->latitud) <= 90 && abs((float) $branch->longitud) <= 180
                ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode("$branch->latitud,$branch->longitud")
                : null;
        }

        $text = "*$branch->nombre*\nHorario: " . ($schedule ?: 'Por confirmar') . "\nDirección: " . ($address ?: 'Por confirmar');
        if ($map) {
            $text .= "\nMapa: $map";
        }

        return [$text . "\n\nEscribe otro número de sucursal o 0 para volver al menú.", 'branch'];
    }
}

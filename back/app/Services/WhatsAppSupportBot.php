<?php

namespace App\Services;

use App\Models\Agencia;
use App\Models\SupportCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WhatsAppSupportBot
{
    private const MENU = [
        1 => 'Horarios y ubicación',
        2 => 'Consultar producto',
        3 => 'Hacer un pedido',
        4 => 'Hablar con persona',
    ];
    private const HANDOFF = 'Tu mensaje quedó en la bandeja de atención. Una persona de nuestro equipo continuará la conversación.';

    public function __construct(private WhatsAppTestReplies $whatsapp, private SupportRealtime $realtime)
    {
    }

    public function enabled(): bool
    {
        return config('services.whatsapp_webhook.bot_enabled', false) && $this->whatsapp->isConfigured();
    }

    public function respond(int $caseId, int $inboundId, ?string $choice = null): void
    {
        $updated = DB::transaction(function () use ($caseId, $inboundId, $choice) {
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

            [$text, $step, $interactive, $followUp] = $this->nextReply($case->whatsapp_bot_step, $inbound->text, $choice) + [2 => null, 3 => null];
            // El bloqueo ordena el envío con los cambios a atención manual. No se reintenta la transacción después del envío.
            $state = $this->sendReply($case, $text, $interactive);
            if ($followUp !== null && $state !== 'failed') {
                $this->sendReply($case, ...$followUp);
            }

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

    private function sendReply(SupportCase $case, string $text, ?array $interactive): string
    {
        [$state, $messageId] = $this->whatsapp->send($case->whatsapp_recipient, $text, $interactive);
        if ($interactive !== null && $state === 'failed') {
            [$state, $messageId] = $this->whatsapp->send($case->whatsapp_recipient, $text);
        }

        $reply = $case->messages()->make(['author' => 'bot', 'text' => $text, 'whatsapp_state' => $state]);
        $reply->whatsapp_message_id = $messageId;
        $reply->save();

        return $state;
    }

    private function nextReply(?string $step, string $message, ?string $choice): array
    {
        $option = Str::lower(Str::ascii(trim($message)));
        if (in_array($option, ['persona', 'asesor', 'humano'], true)) {
            return [self::HANDOFF, null];
        }
        if ($choice !== null) {
            [, $type, $option] = explode(':', $choice);
            $step = $type === 'branch' ? 'branch' : 'menu';
        } elseif ($step === null) {
            return $this->menu("Hola, somos Farmacias Santidad Divina.\n\n");
        }
        if ($option === '0' || $option === 'menu') {
            return $this->menu();
        }

        return match ($step) {
            'product', 'order' => [self::HANDOFF, null],
            'branch' => $this->branchDetails($option),
            default => match ($option) {
                '1' => $this->branchList(),
                '2' => ["Indícanos el nombre del producto, su presentación, la cantidad y la sucursal que prefieres.\n\nEscribe 0 para volver al menú.", 'product'],
                '3' => ["Escribe los productos, las cantidades y la sucursal que prefieres. El equipo revisará tu solicitud contigo.\n\nEscribe 0 para volver al menú.", 'order'],
                '4' => [self::HANDOFF, null],
                default => $this->menu(),
            },
        };
    }

    private function menu(string $intro = ''): array
    {
        $body = $intro . '¿En qué podemos ayudarte?';
        $first = $this->menuButtons($body, [1, 2, 3]);
        $second = $this->menuButtons('¿Prefieres hablar con una persona?', [4]);

        return [$first[0], 'menu', $first[1], $second];
    }

    private function menuButtons(string $body, array $options): array
    {
        $lines = [];
        $buttons = [];
        foreach ($options as $option) {
            $title = self::MENU[$option];
            $lines[] = "$option. $title";
            $buttons[] = ['type' => 'reply', 'reply' => ['id' => "support:menu:$option", 'title' => $title]];
        }

        return [
            $body . "\n\nResponde con el número de tu opción:\n" . implode("\n", $lines),
            ['type' => 'button', 'body' => ['text' => $body], 'action' => ['buttons' => $buttons]],
        ];
    }

    private function branchList(): array
    {
        $branches = Agencia::where('status', 'ACTIVO')->orderBy('nombre')->orderBy('id')->get(['id', 'nombre']);
        if ($branches->isEmpty()) {
            return $this->menu("Por el momento no tenemos sucursales disponibles en el menú.\n\n");
        }

        $options = $branches->map(fn (Agencia $branch) => "$branch->id. $branch->nombre")->implode("\n");

        $interactive = null;
        if ($branches->count() <= 10) {
            $rows = $branches->map(fn (Agencia $branch) => [
                'id' => "support:branch:$branch->id",
                'title' => mb_substr("$branch->id. $branch->nombre", 0, 24),
                'description' => mb_substr($branch->nombre, 0, 72),
            ])->all();
            $interactive = $this->listMessage("Elige una sucursal para ver su horario y dirección.\n\nEscribe 0 para volver al menú.", 'Ver sucursales', 'Sucursales', $rows);
        }

        return ["Elige una sucursal por su número:\n$options\n\nEscribe 0 para volver al menú.", 'branch', $interactive];
    }

    private function listMessage(string $body, string $button, string $section, array $rows): array
    {
        return [
            'type' => 'list',
            'body' => ['text' => $body],
            'action' => ['button' => $button, 'sections' => [['title' => $section, 'rows' => $rows]]],
        ];
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

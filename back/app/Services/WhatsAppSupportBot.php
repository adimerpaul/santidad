<?php

namespace App\Services;

use App\Models\Agencia;
use App\Models\SupportCase;
use App\Models\SupportMessage;
use Illuminate\Support\Facades\DB;

class WhatsAppSupportBot
{
    private const MENU = [
        1 => 'Horarios y ubicación',
        2 => 'Consultar producto',
        3 => 'Hacer un pedido',
        4 => 'Hablar con persona',
    ];
    public function __construct(private WhatsAppTestReplies $whatsapp, private SupportRealtime $realtime,
        private WhatsAppConversation $conversation)
    {
    }

    public function enabled(): bool
    {
        return config('services.whatsapp_webhook.bot_enabled', false) && $this->whatsapp->isConfigured();
    }

    public function respond(int $caseId, int $inboundId, ?string $choice = null, ?array $location = null, bool $allowAi = false): void
    {
        $original = SupportCase::find($caseId);
        $inbound = $original && $this->canRespond($original) ? $this->currentInbound($original, $inboundId) : null;
        if (!$inbound) {
            return;
        }
        $plan = $this->conversation->plan($original, $inbound->text, $choice, $location, $allowAi);

        $updated = DB::transaction(function () use ($caseId, $inboundId, $choice, $original, $plan, $inbound) {
            $case = SupportCase::lockForUpdate()->find($caseId);
            if (!$case || !$this->canRespond($case) || !$this->currentInbound($case, $inboundId) ||
                $case->getRawOriginal('whatsapp_context') !== $original->getRawOriginal('whatsapp_context') ||
                $case->whatsapp_bot_step !== $original->whatsapp_bot_step || $case->agencia_id !== $original->agencia_id) {
                return null;
            }

            [$text, $step, $interactive, $followUp] = ($plan['reply'] ?? $this->nextReply($case->whatsapp_bot_step, $inbound->text, $choice)) + [2 => null, 3 => null];
            if ($plan && $case->whatsapp_bot_step === null) {
                $text = "Hola, soy el asistente virtual de Farmacias Santidad Divina.\n\n" . $text;
            }
            // El bloqueo ordena el envío con los cambios a atención manual. No se reintenta la transacción después del envío.
            $state = $this->sendReply($case, $text, $interactive);
            if ($followUp !== null && $state === 'accepted') {
                $this->sendReply($case, ...$followUp);
            }

            $case->whatsapp_indicator = $plan['indicator'] ?? (in_array($step, ['branch', 'product', 'order'], true) ? 'waiting' : 'automatic');
            $case->whatsapp_handoff_reason = $plan['reason'] ?? null;
            if ($step === null) {
                $case->mode = 'manual';
                $case->whatsapp_bot_step = null;
                $case->status = 'pending';
                $branchId = $plan['branch'] ?? null;
                if (!$case->agencia_id && $branchId && Agencia::where('status', 'ACTIVO')->whereKey($branchId)->exists()) {
                    $case->agencia_id = $branchId;
                }
            } elseif ($state === 'accepted') {
                $case->whatsapp_bot_step = $step;
            }
            if ($state === 'accepted' || $step === null) {
                $context = $plan['context'] ?? match ($step) {
                    'branch' => ['intents' => ['horario', 'direccion'], 'pending' => 'branch'],
                    'product' => ['intents' => ['producto'], 'pending' => 'product'],
                    'order' => ['intents' => ['pedido'], 'pending' => 'product'],
                    default => [],
                };
                $case->whatsapp_context = $context ?: null;
                $retention = max(1, min(168, (int) config('services.whatsapp_webhook.context_hours', 24)));
                $case->whatsapp_context_expires_at = $context ? now()->addHours($retention) : null;
            }
            $case->touch();

            return ['id' => $caseId, 'branch' => $case->agencia_id === null ? null : (int) $case->agencia_id];
        });

        if ($updated) {
            $this->realtime->notify([$updated]);
        }
    }

    private function canRespond(SupportCase $case): bool
    {
        return $this->enabled() && $case->mode === 'bot' && $case->status !== 'resolved'
            && $case->whatsapp_phone_id === $this->whatsapp->phoneId() && $case->whatsapp_recipient
            && $case->whatsapp_last_inbound > now()->timestamp - 86400;
    }

    private function currentInbound(SupportCase $case, int $inboundId): ?SupportMessage
    {
        $inbound = $case->messages()->where('author', 'customer')->latest('id')->first();

        return $inbound?->id === $inboundId && !$case->messages()->where('author', 'bot')->where('id', '>', $inboundId)->exists()
            ? $inbound : null;
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
        $option = $choice ? substr($choice, -1) : trim($message);

        return match ($option) {
            '1' => $this->branchList(),
            '2' => ['Indícanos el nombre del producto, su presentación y la sucursal que prefieres.', 'product'],
            '3' => ['Dime el nombre, la presentación y la cantidad de un producto no regulado para que el equipo revise tu solicitud.', 'order'],
            default => $this->menu($step === null ? "Hola, soy el asistente virtual de Farmacias Santidad Divina.\n\n" : ''),
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

}

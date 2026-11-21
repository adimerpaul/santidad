<?php

namespace App\Services;

use App\Models\Agencia;
use App\Models\Product;
use App\Models\SupportCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Throwable;

class WhatsAppConversation
{
    private const BRANCH_INTENTS = ['horario', 'abierta', 'direccion', 'cercana'];
    private const PRODUCT_INTENTS = ['producto', 'disponibilidad', 'precio', 'pedido'];
    private const HANDOFFS = [
        'humano' => 'Solicitó atención humana',
        'queja' => 'Queja o reclamo',
        'seguimiento' => 'Seguimiento de una consulta',
        'farmaceutica' => 'Solicitó orientación farmacéutica',
        'manipulacion' => 'Mensaje fuera del alcance del asistente',
    ];

    public function __construct(private WhatsAppIntentDetector $language, private WhatsAppBranchDirectory $directory,
        private WhatsAppProductCatalog $catalog, private AIIntentService $ai)
    {
    }

    public function plan(SupportCase $case, string $message, ?string $choice, ?array $location = null, bool $allowAi = true): ?array
    {
        $step = $case->whatsapp_bot_step;
        $expired = $case->whatsapp_context_expires_at?->isPast();
        $memory = !$expired ? ($case->whatsapp_context ?? []) : [];
        if ($expired && in_array($step, ['branch', 'product', 'order'], true)) {
            $step = 'conversation';
        }
        $text = $this->language->normalize(mb_substr($message, 0, 500));
        $intents = $this->language->detect($text);
        $intent = $intents[0];
        if (($intent === 'saludo' && $step === null) || in_array($text, ['0', 'menu'], true)) {
            return null;
        }
        if ($intent === 'saludo') {
            return $this->result('Dime cómo puedo ayudarte.', $memory);
        }
        if ($intent === 'agradecimiento' || $intent === 'despedida') {
            return $this->result($intent === 'agradecimiento' ? '¡Con gusto!' : '¡Hasta luego!', $memory);
        }

        $branches = $this->directory->active();
        $matches = $this->directory->match($text, $branches);
        $exact = $this->directory->match($text, $matches, false);
        $explicitBranch = $matches->count() === 1 ? (int) $matches->first()->id : null;
        $branchConfirmed = $exact->count() === 1;
        if (isset(self::HANDOFFS[$intent]) || $choice === 'support:menu:4' || ($step === 'menu' && $text === '4')) {
            $intent = isset(self::HANDOFFS[$intent]) ? $intent : 'humano';
            if (!$branchConfirmed && ($matches->isNotEmpty() || preg_match('/\b(otra|otro)\b/', $text))) {
                unset($memory['branch_confirmed']);
            }
            return $this->handoff($memory, self::HANDOFFS[$intent], $branchConfirmed ? $explicitBranch : null,
                $intent === 'farmaceutica' ? 'Para orientación farmacéutica, consulta a un profesional autorizado. Tu consulta quedó pendiente para que el equipo pueda orientarte sobre el canal adecuado.' : null);
        }

        if (preg_match('/^support:menu:[1-3]$/', $choice ?? '') || ($step === 'menu' && !$memory && preg_match('/^[1-3]$/', $text))) {
            return null;
        }
        if (preg_match('/^support:branch:([1-9][0-9]{0,17})$/', $choice ?? '', $parts)
            || ($step === 'branch' && preg_match('/^([1-9][0-9]{0,17})$/', $text, $parts))) {
            $explicitBranch = $branches->contains('id', (int) $parts[1]) ? (int) $parts[1] : null;
            $branchConfirmed = $explicitBranch !== null;
            $intents = $memory['intents'] ?? ['horario', 'direccion'];
            $matches = collect();
        } elseif ($location !== null) {
            $nearest = $this->directory->nearest($branches, $location[0], $location[1]);
            $explicitBranch = $nearest ? (int) $nearest->id : null;
            $intents = ['cercana'];
            $matches = collect();
            if (!$nearest) {
                return $this->result('Por ahora no tengo ubicaciones confirmadas para comparar. ¿Qué sucursal deseas consultar?', $memory, 'waiting');
            }
        }

        $branchId = $explicitBranch ?? ($memory['branch_id'] ?? null);
        if ($explicitBranch === null && preg_match('/\b(otra|otro)\b/', $text)) {
            $branchId = null;
            $memory['pending'] = 'branch';
        }
        if ($matches->count() > 1) {
            $branchId = null;
        }
        $branch = $branches->firstWhere('id', $branchId);
        $previous = $memory['intents'] ?? match ($step) {
            'product' => ['producto'], 'order' => ['pedido'], 'branch' => ['horario', 'direccion'], default => [],
        };
        [$date, $time, $timeQuestion, $ambiguousTime] = $this->directory->when($text) + [3 => null];
        if (isset($memory['ambiguous_time']) && preg_match('/^(a[ .]*m[ .]*|p[ .]*m[ .]*|de la manana|de la tarde|de la noche)$/', $text)) {
            [$date, $time, $timeQuestion] = $this->directory->when('a las ' . $memory['ambiguous_time'] . ' ' . $text);
        }
        $followsContext = $intent === 'desconocida' && ($explicitBranch !== null || $date !== null || $time !== null || $timeQuestion
            || !empty($memory['pending']) || preg_match('/\b(esa|ese|otra|otro|ahi|el de)\b/', $text));
        if (($followsContext || in_array($step, ['product', 'order'], true)) && $previous && $intents === ['desconocida']) {
            $intents = $previous;
        }
        if ($intents === ['desconocida'] && $explicitBranch !== null) {
            $intents = ['horario', 'direccion'];
        }

        $indicator = 'automatic';
        $clarification = null;
        if ($intents === ['desconocida'] && $allowAi && $this->ai->enabled()) {
            preg_match_all('/\b(horari[oa]s?|sucursal|atiend\w{0,3}|abiert[ao]s?|cerrad[ao]s?|hasta|hora|ubicacion|direccion|calle|cerca|hoy|manana|domingo|feriado|otra|ahi|esa|precio|producto|stock|voy|ir|pasar|puedo|temprano|tarde|noche|turno|encuentro)\b/', $text, $words);
            if ($words[0]) {
                try {
                    $interpretation = $this->ai->interpret($case->id, implode(' ', $words[0]), $explicitBranch ? [$explicitBranch] : []);
                } catch (Throwable $ignored) {
                    $interpretation = null;
                }
                $indicator = $interpretation === null ? 'interpretation_error' : 'ai';
                if (($interpretation['confidence'] ?? 0) >= 0.8) {
                    if ($interpretation['needs_human']) {
                        return $this->handoff($memory, 'La consulta necesita revisión del equipo', $explicitBranch);
                    }
                    if (!$interpretation['needs_clarification'] && $interpretation['intents']) {
                        $intents = $interpretation['intents'];
                    } elseif ($interpretation['needs_clarification']) {
                        $clarification = match ($interpretation['clarification']) {
                            'branch' => '¿Qué sucursal deseas consultar?',
                            'date' => '¿Para qué fecha?',
                            'time' => '¿A qué hora?',
                            'product' => '¿Qué producto y presentación buscas?',
                            default => null,
                        };
                        if ($clarification && $interpretation['intents']) {
                            $memory['intents'] = $interpretation['intents'];
                            $memory['pending'] = $interpretation['clarification'];
                        }
                    }
                }
            }
        }
        if ($intents === ['desconocida']) {
            $memory['unknown'] = ($memory['unknown'] ?? 0) + 1;
            return $memory['unknown'] >= 2
                ? $this->handoff($memory, 'El asistente no pudo comprender la consulta', $explicitBranch)
                : $this->result($clarification ?? '¿Puedes contarme un poco más? Puedo ayudarte con horarios, direcciones o consultas de productos. También puedes pedir hablar con una persona.', $memory, $indicator === 'automatic' ? 'waiting' : $indicator);
        }

        $sameTopic = $followsContext || ($previous && !array_diff($intents, $previous));
        $memory['date'] = $date ?? ($sameTopic ? ($memory['date'] ?? null) : null);
        $memory['time'] = $time ?? ($sameTopic && $date === null ? ($memory['time'] ?? null) : null);
        $memory['ambiguous_time'] = $ambiguousTime;
        $memory['intents'] = $intents;
        $memory['branch_confirmed'] = $explicitBranch !== null ? $branchConfirmed
            : ($branch && !empty($memory['branch_confirmed']));
        $memory['branch_id'] = $branch?->id;
        $memory['unknown'] = 0;
        if ($timeQuestion && array_intersect($intents, ['horario', 'abierta'])) {
            $memory['pending'] = 'time';
            return $this->result($timeQuestion, $memory, 'waiting');
        }

        $branchesRequested = array_intersect($intents, self::BRANCH_INTENTS);
        $productsRequested = array_intersect($intents, self::PRODUCT_INTENTS);
        if ($branchesRequested && $productsRequested) {
            $memory['intents'] = array_values($branchesRequested);
            $memory['pending'] = 'branch';
            return $this->result('¿Quieres consultar primero la sucursal o el producto?', $memory, 'waiting');
        }
        if ($productsRequested) {
            if ($step === 'order') {
                $productsRequested[] = 'pedido';
            }
            return $this->product($message, $text, $branch, $memory, $productsRequested, $indicator);
        }

        return $this->branch($branches, $branch, $memory, $branchesRequested, $indicator, $location !== null);
    }

    private function branch(Collection $branches, ?Agencia $branch, array $memory, array $intents, string $indicator, bool $fromLocation): array
    {
        $now = CarbonImmutable::now(WhatsAppBranchDirectory::ZONE);
        $date = $memory['date'] ? CarbonImmutable::parse($memory['date'], WhatsAppBranchDirectory::ZONE) : $now;
        $time = $memory['time'] ?? ($date->isSameDay($now) ? $now->format('H:i') : null);
        $open = in_array('abierta', $intents, true);
        $hours = $open || in_array('horario', $intents, true);
        $address = (bool) array_intersect(['direccion', 'cercana'], $intents);
        if (!$branch && in_array('cercana', $intents, true)) {
            $memory['pending'] = 'location';
            return $this->result('Comparte tu ubicación en WhatsApp o dime qué sucursal deseas consultar.', $memory, 'waiting');
        }
        if (!$branch && $open && empty($memory['pending'])) {
            $unknown = false;
            $openNames = [];
            foreach ($branches as $candidate) {
                $windows = $this->directory->scheduled($candidate, $date);
                $unknown = $unknown || $windows === null;
                if ($this->isOpen($windows ?? [], $time)) {
                    $openNames[] = $candidate->nombre;
                }
            }
            $label = $date->format('d/m/Y') . ($time ? " a las $time" : '');
            $result = $openNames ? "Para el $label, el horario indica atención en " . implode(', ', $openNames) . '.'
                : "Para el $label, no figura atención en las sucursales con horario programado.";
            if ($unknown || $branches->isEmpty()) {
                $result = $openNames ? $result . ' Falta confirmar el horario de otras sucursales.'
                    : "No puedo confirmar qué sucursales atienden el $label con los horarios registrados.";
            }
            return $this->result($result, $memory, $indicator);
        }
        if (!$branch) {
            if (($memory['pending'] ?? null) === 'branch') {
                $memory['attempts'] = ($memory['attempts'] ?? 0) + 1;
                if ($memory['attempts'] >= 2) {
                    return $this->handoff($memory, 'Falta confirmar la sucursal de atención', null);
                }
            }
            $memory['pending'] = 'branch';
            return $this->result('¿Qué sucursal deseas consultar?' . ($branches->isNotEmpty() ? ' ' . $branches->take(7)->pluck('nombre')->implode(', ') . '.' : ''), $memory, $indicator === 'ai' ? 'ai' : 'waiting');
        }

        $memory['pending'] = null;
        unset($memory['attempts']);
        $result = $this->directory->details($branch, $hours, $address);
        if ($hours && ($open || $memory['date'] || $memory['time'])) {
            $windows = $this->directory->scheduled($branch, $date);
            if ($windows === null) {
                $result .= "\nPor ahora solo tengo el horario informado. No puedo confirmar la atención para ese momento.";
            } else {
                $result = $this->directory->details($branch, false, $address);
                $label = $date->format('d/m/Y');
                $ranges = array_map(fn ($window) => implode(' a ', $window), $windows);
                $result .= "\nHorario programado para el $label: " . ($ranges ? implode(', ', $ranges) . '.' : 'sin atención programada.');
                if ($time !== null && ($open || $memory['time'])) {
                    $result .= $this->isOpen($windows, $time) ? "\nEl horario indica atención a las $time." : "\nEl horario no indica atención a las $time.";
                }
            }
        }
        if ($fromLocation) {
            $result = "La sucursal más cercana de las que tienen ubicación registrada es:\n$result";
        }
        return $this->result($result, $memory, $indicator);
    }

    private function isOpen(array $windows, ?string $time): bool
    {
        return $time === null ? $windows !== [] : collect($windows)->contains(fn ($window) => $time >= $window[0] && $time < $window[1]);
    }

    private function product(string $message, string $normalized, ?Agencia $branch, array $memory, array $intents, string $indicator): array
    {
        $order = in_array('pedido', $intents, true);
        $availability = in_array('disponibilidad', $intents, true);
        $priceRequested = in_array('precio', $intents, true);
        if (preg_match('/\b(\d{1,4}|una|uno|dos|tres|cuatro|cinco)\s*(unidades?|piezas?)\b/', $normalized, $quantity)) {
            $numbers = ['una' => 1, 'uno' => 1, 'dos' => 2, 'tres' => 3, 'cuatro' => 4, 'cinco' => 5];
            $memory['quantity'] = max(1, $numbers[$quantity[1]] ?? (int) $quantity[1]);
            $message = str_replace($quantity[0], '', $normalized);
        }
        $product = null;
        $choices = $memory['product_choices'] ?? [];
        if ($choices && ($memory['pending'] ?? null) === 'presentation') {
            if (ctype_digit($normalized) && isset($choices[(int) $normalized - 1])) {
                $product = $this->catalog->find($choices[(int) $normalized - 1]);
            } elseif (preg_match('/\bel de (\d{1,4})\b/', $normalized, $size)) {
                $options = collect($choices)->map(fn ($id) => $this->catalog->find($id))->filter()
                    ->filter(fn (Product $item) => preg_match('/\b' . $size[1] . '(?:\s*(?:ml|g|gr))?\b/i', $item->nombre));
                $product = $options->count() === 1 ? $options->first() : null;
            }
        }
        if ($branch) {
            $message = $this->directory->withoutBranch($message, $branch);
        }
        $contextReference = $branch || preg_match('/\b(el de|la de|ese|esa|otra|otro|ahi|precio|stock)\b/', $normalized);
        $matches = $product ? collect([$product]) : $this->catalog->search($message);
        if ($matches->isEmpty() && $contextReference && isset($memory['product_id']) && $this->catalog->isReference($message)) {
            $remembered = $this->catalog->find($memory['product_id']);
            $matches = $remembered ? collect([$remembered]) : collect();
        }
        if ($matches->isEmpty()) {
            if (!$choices && $this->catalog->isReference($message) && ($memory['pending'] ?? null) !== 'product') {
                $memory['pending'] = 'product';
                return $this->result('¿Qué producto y presentación buscas?', $memory, 'waiting');
            }
            return $this->handoff($memory, 'La consulta de producto necesita revisión del equipo', null,
                'El equipo puede orientarte sobre cómo continuar con tu consulta. La dejé pendiente para revisión.');
        }
        if ($matches->count() > 1) {
            $memory['product_choices'] = $matches->pluck('id')->all();
            $memory['pending'] = 'presentation';
            $options = $matches->values()->map(fn (Product $item, $index) => ($index + 1) . ". $item->nombre")->implode("\n");
            return $this->result("¿Cuál presentación buscas? Responde con su número.\n$options", $memory, 'waiting');
        }
        $product = $matches->first();
        if (($memory['product_id'] ?? null) !== $product->id && !isset($quantity[1])) {
            unset($memory['quantity']);
        }
        $memory['product_id'] = $product->id;
        $memory['product_choices'] = [];
        $memory['pending'] = null;
        if (!$branch && ($order || $availability || $priceRequested)) {
            $memory['pending'] = 'product_branch';
            return $this->result("Encontré *$product->nombre*. ¿En qué sucursal deseas consultarlo?", $memory, 'waiting');
        }
        if ($order) {
            return $this->handoff($memory, 'Solicitud sobre un producto autorizado, pendiente de revisión', null);
        }
        $lines = ["*$product->nombre*"];
        if ($branch) {
            $lines[] = "Sucursal: $branch->nombre.";
        }
        if ($availability) {
            $quantity = $memory['quantity'] ?? 1;
            $confirmed = $this->catalog->availability($product, $branch, $quantity);
            $lines[] = match ($confirmed) {
                true => $quantity > 1 ? "El inventario indica disponibilidad para $quantity unidades." : 'El inventario indica disponibilidad.',
                false => $quantity > 1 ? 'El inventario no indica disponibilidad para esa cantidad.' : 'El inventario no indica disponibilidad.',
                null => 'Por ahora no puedo confirmar la disponibilidad.',
            };
        }
        if ($priceRequested) {
            $price = $this->catalog->price($product, $branch);
            $lines[] = $price ? "Precio en sucursal: Bs $price." : 'El precio se puede consultar con el equipo.';
        }
        return $this->result(implode("\n", $lines), $memory, $indicator);
    }

    private function result(string $text, array $memory, string $indicator = 'automatic'): array
    {
        return ['reply' => [$text, 'conversation'], 'context' => $memory, 'indicator' => $indicator, 'reason' => null, 'branch' => null];
    }

    private function handoff(array $memory, string $reason, ?int $branch, ?string $text = null): array
    {
        return ['reply' => [$text ?? 'Tu consulta quedó pendiente para que una persona del equipo pueda continuar contigo.', null],
            'context' => [], 'indicator' => 'human', 'reason' => $reason,
            'branch' => $branch ?? (!empty($memory['branch_confirmed']) ? $memory['branch_id'] : null)];
    }
}

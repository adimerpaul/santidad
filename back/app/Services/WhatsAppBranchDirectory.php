<?php

namespace App\Services;

use App\Models\Agencia;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class WhatsAppBranchDirectory
{
    public const ZONE = 'America/La_Paz';
    private const DAYS = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];

    public function __construct(private WhatsAppIntentDetector $language)
    {
    }

    public function active(): Collection
    {
        return Agencia::where('status', 'ACTIVO')->orderBy('nombre')->orderBy('id')->get();
    }

    public function match(string $message, Collection $branches, bool $allowFuzzy = true): Collection
    {
        $text = trim(preg_replace('/\s+/', ' ', str_replace('.', ' ', $this->language->normalize($message))));
        $words = preg_split('/\s+/', $text);
        $exact = $branches->filter(function (Agencia $branch) use ($text) {
            foreach ($this->aliases($branch) as $alias) {
                if (str_contains(" $text ", " $alias ")) {
                    return true;
                }
            }
            return false;
        });
        if ($exact->isNotEmpty() || !$allowFuzzy) {
            return $exact->values();
        }

        return $branches->filter(function (Agencia $branch) use ($words) {
            foreach ($this->aliases($branch) as $alias) {
                if (strlen($alias) < 4) {
                    continue;
                }
                $size = str_word_count($alias);
                for ($index = 0; $index + $size <= count($words); $index++) {
                    $phrase = implode(' ', array_slice($words, $index, $size));
                    if (strlen($phrase) >= 4 && levenshtein($alias, $phrase) === 1) {
                        return true;
                    }
                }
            }
            return false;
        })->values();
    }

    private function aliases(Agencia $branch): array
    {
        $name = str_replace('.', ' ', $this->language->normalize($branch->nombre));
        $name = trim(preg_replace('/\b(farmacias?|suc|sucursal|agencia|la|del|de)\b/', ' ', $name));
        $name = trim(preg_replace('/\s+/', ' ', $name));

        return array_unique(array_filter([$name, $this->language->normalize((string) $branch->sucursal), ...explode(' ', $name)],
            fn ($alias) => strlen($alias) >= 3 && !in_array($alias, ['terminal', 'casa', 'matriz', 'central'], true) || $alias === $name && strlen($alias) >= 3));
    }

    public function withoutBranch(string $message, Agencia $branch): string
    {
        $text = $this->language->normalize($message);
        $aliases = $this->aliases($branch);
        usort($aliases, fn ($first, $second) => strlen($second) <=> strlen($first));
        foreach ($aliases as $alias) {
            $text = preg_replace('/\b' . preg_quote($alias, '/') . '\b/', ' ', $text);
        }

        return trim($text);
    }

    public function when(string $text): array
    {
        $text = preg_replace('/\b([ap])\s*\.\s*m\.?/', '$1m', $text);
        $now = CarbonImmutable::now(self::ZONE);
        $date = null;
        if (preg_match('/\b(\d{4})-(\d{1,2})-(\d{1,2})\b/', $text, $parts)) {
            if (!checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
                return [null, null, '¿Qué fecha deseas consultar?'];
            }
            $date = sprintf('%04d-%02d-%02d', $parts[1], $parts[2], $parts[3]);
        } elseif (preg_match('/\b(\d{1,2})\/(\d{1,2})(?:\/(\d{4}))?\b/', $text, $parts)) {
            $year = (int) ($parts[3] ?? $now->year);
            if (!checkdate((int) $parts[2], (int) $parts[1], $year)) {
                return [null, null, '¿Qué fecha deseas consultar?'];
            }
            $date = sprintf('%04d-%02d-%02d', $year, $parts[2], $parts[1]);
        } elseif (preg_match('/\b(pasado manana|manana|hoy)\b/', $text, $parts)) {
            $date = $now->addDays(match ($parts[1]) { 'pasado manana' => 2, 'manana' => 1, default => 0 })->toDateString();
        } elseif (preg_match('/\b(' . implode('|', self::DAYS) . ')\b/', $text, $parts)) {
            $day = array_search($parts[1], self::DAYS, true) + 1;
            $date = $now->addDays(($day - $now->isoWeekday() + 7) % 7)->toDateString();
        } elseif (preg_match('/\bferiados?\b/', $text)) {
            return [null, null, '¿Qué fecha del feriado deseas consultar?'];
        }

        $time = null;
        if (preg_match('/\b(?:(?:a las?|tipo)\s+(\d{1,2})|(\d{1,2})(?=\s*(?::|am\b|pm\b)))\s*(?::([0-9]{2})|\s+y\s+(media|cuarto))?\s*(am|pm|de la manana|de la tarde|de la noche|de (?:la )?madrugada)?\b/', $text, $parts)) {
            $hour = (int) ($parts[1] !== '' ? $parts[1] : $parts[2]);
            $minute = (int) (($parts[3] ?? '') !== '' ? $parts[3] : match ($parts[4] ?? '') { 'media' => 30, 'cuarto' => 15, default => 0 });
            $period = $parts[5] ?? '';
            if ($minute > 59 || $hour > 23 || ($period !== '' && ($hour > 12 || $hour === 0))) {
                return [$date, null, '¿Qué hora deseas consultar?'];
            }
            if ($period === '' && $hour >= 1 && $hour <= 12) {
                $candidate = sprintf('%d:%02d', $hour, $minute);
                return [$date, null, "¿Te refieres a las $candidate a. m. o p. m.?", $candidate];
            }
            if ($period !== '') {
                $hour = $hour % 12 + (in_array($period, ['pm', 'de la tarde', 'de la noche'], true) ? 12 : 0);
            }
            $time = sprintf('%02d:%02d', $hour, $minute);
        }

        return [$date, $time, null];
    }

    public function details(Agencia $branch, bool $schedule, bool $address): string
    {
        $lines = ["*$branch->nombre*"];
        if ($schedule) {
            $text = implode(' · ', array_filter([trim((string) $branch->atencion), trim((string) $branch->horario)]));
            if ($text) {
                $lines[] = 'Horario informado: ' . $text;
            } elseif ($this->scheduled($branch, CarbonImmutable::now(self::ZONE)) !== null) {
                $weekly = $branch->whatsapp_hours['weekly'];
                foreach (self::DAYS as $index => $day) {
                    $ranges = array_map(fn ($interval) => $interval['start'] . ' a ' . $interval['end'], $weekly[$index + 1]);
                    if ($ranges) {
                        $lines[] = ucfirst($day) . ': ' . implode(', ', $ranges);
                    }
                }
                if (count($lines) === 1) {
                    $lines[] = 'Sin atención semanal programada.';
                }
            } else {
                $lines[] = 'Horario por confirmar.';
            }
        }
        if ($address) {
            $lines[] = 'Dirección: ' . (trim((string) $branch->direccion) ?: 'por confirmar.');
            $map = trim((string) $branch->gps);
            if (!filter_var($map, FILTER_VALIDATE_URL) || parse_url($map, PHP_URL_SCHEME) !== 'https') {
                $map = is_numeric($branch->latitud) && is_numeric($branch->longitud)
                    && abs((float) $branch->latitud) <= 90 && abs((float) $branch->longitud) <= 180
                    ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode("$branch->latitud,$branch->longitud")
                    : '';
            }
            if ($map) {
                $lines[] = 'Mapa: ' . $map;
            }
        }

        return implode("\n", $lines);
    }

    public function scheduled(Agencia $branch, CarbonImmutable $date): ?array
    {
        $hours = $branch->whatsapp_hours;
        if (!is_array($hours) || !is_array($hours['weekly'] ?? null) || array_diff(range(1, 7), array_keys($hours['weekly']))) {
            return null;
        }

        $date = $date->setTimezone(self::ZONE)->startOfDay();
        $exceptions = is_array($hours['exceptions'] ?? null) ? $hours['exceptions'] : [];
        $today = $exceptions[$date->toDateString()] ?? $hours['weekly'][$date->isoWeekday()];
        $windows = [];
        if (!array_key_exists($date->toDateString(), $exceptions)) {
            $yesterday = $date->subDay();
            foreach ($exceptions[$yesterday->toDateString()] ?? $hours['weekly'][$yesterday->isoWeekday()] as $interval) {
                if ($interval['end'] < $interval['start'] && $interval['end'] !== '00:00') {
                    $windows[] = ['00:00', $interval['end']];
                }
            }
        }
        foreach ($today as $interval) {
            $windows[] = [$interval['start'], $interval['end'] <= $interval['start'] ? '24:00' : $interval['end']];
        }

        return $windows;
    }

    public function nearest(Collection $branches, float $lat, float $lng): ?Agencia
    {
        return $branches->filter(fn (Agencia $branch) => is_numeric($branch->latitud) && is_numeric($branch->longitud)
                && abs((float) $branch->latitud) <= 90 && abs((float) $branch->longitud) <= 180)
            ->sortBy(fn (Agencia $branch) => sin(deg2rad((float) $branch->latitud - $lat) / 2) ** 2
                + cos(deg2rad($lat)) * cos(deg2rad((float) $branch->latitud)) * sin(deg2rad((float) $branch->longitud - $lng) / 2) ** 2)
            ->first();
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Agencia;
use Illuminate\Console\Command;
use JsonException;

class ConfigureWhatsAppBranchHours extends Command
{
    protected $signature = 'whatsapp:branch-hours {branch} {file}';
    protected $description = 'Registra horarios semanales y excepciones de una sucursal para WhatsApp';

    public function handle(): int
    {
        $branch = Agencia::find($this->argument('branch'));
        $file = $this->argument('file');
        if (!$branch || !is_file($file) || !is_readable($file) || filesize($file) > 65536) {
            $this->error('Indica una sucursal existente y un archivo JSON legible de hasta 64 KiB.');
            return self::FAILURE;
        }
        $json = file_get_contents($file);
        try {
            $hours = json_decode((string) $json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->error('El archivo no contiene un JSON válido.');
            return self::FAILURE;
        }
        if (!$this->valid($hours)) {
            $this->error('Incluye los días 1 a 7 y usa fechas AAAA-MM-DD e intervalos HH:MM válidos.');
            return self::FAILURE;
        }
        $branch->whatsapp_hours = $hours;
        $branch->save();
        $this->info('Horario guardado.');

        return self::SUCCESS;
    }

    private function valid(mixed $hours): bool
    {
        if (!is_array($hours) || array_diff(array_keys($hours), ['weekly', 'exceptions']) ||
            !is_array($hours['weekly'] ?? null) || count($hours['weekly']) !== 7 ||
            array_diff(range(1, 7), array_keys($hours['weekly']))) {
            return false;
        }
        $exceptions = $hours['exceptions'] ?? [];
        if (!is_array($exceptions)) {
            return false;
        }
        foreach ($exceptions as $date => $intervals) {
            if (!preg_match('/\A(\d{4})-(\d{2})-(\d{2})\z/', (string) $date, $parts) ||
                !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
                return false;
            }
        }
        foreach (array_merge(array_values($hours['weekly']), array_values($exceptions)) as $intervals) {
            if (!is_array($intervals) || !array_is_list($intervals) || count($intervals) > 8) {
                return false;
            }
            foreach ($intervals as $interval) {
                if (!is_array($interval) || count($interval) !== 2 || !is_string($interval['start'] ?? null) || !is_string($interval['end'] ?? null)
                    || !preg_match('/\A(?:[01]\d|2[0-3]):[0-5]\d\z/', $interval['start'])
                    || !preg_match('/\A(?:(?:[01]\d|2[0-3]):[0-5]\d|24:00)\z/', $interval['end'])
                    || $interval['start'] === $interval['end']) {
                    return false;
                }
            }
        }
        return true;
    }
}

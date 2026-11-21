<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MaintainWhatsAppAssistant extends Command
{
    protected $signature = 'whatsapp:assistant {action : prune o metrics}';
    protected $description = 'Limpia contexto vencido o consulta el estado agregado del asistente';

    public function handle(): int
    {
        if ($this->argument('action') === 'prune') {
            $expired = DB::table('support_cases')->where('whatsapp_context_expires_at', '<=', now());
            $contexts = $expired->update([
                'whatsapp_context' => null, 'whatsapp_context_expires_at' => null,
                'whatsapp_bot_step' => DB::raw("CASE WHEN whatsapp_bot_step IS NULL THEN NULL ELSE 'conversation' END"),
            ]);
            DB::table('whatsapp_ai_limits')->where('expires_at', '<=', now('UTC'))->delete();
            $this->info("Contextos eliminados: $contexts");
            return self::SUCCESS;
        }
        if ($this->argument('action') === 'metrics') {
            $rows = DB::table('support_cases')->where('source', 'whatsapp')->whereNotNull('whatsapp_indicator')
                ->select('whatsapp_indicator')->selectRaw('COUNT(*) as total')->groupBy('whatsapp_indicator')->get();
            $this->table(['Estado', 'Casos'], $rows->map(fn ($row) => [$row->whatsapp_indicator, $row->total]));
            return self::SUCCESS;
        }
        $this->error('Acción inválida. Usa prune o metrics.');

        return self::FAILURE;
    }
}

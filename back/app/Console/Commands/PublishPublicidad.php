<?php

namespace App\Console\Commands;

use App\Services\PublicidadSyncService;
use Illuminate\Console\Command;

class PublishPublicidad extends Command
{
    protected $signature = 'publicidad:publish-pending {--daily : Publica todas las sucursales desde el inicio}';
    protected $description = 'Reintenta programaciones de publicidad pendientes de entregar al socket';

    public function handle(PublicidadSyncService $service): int
    {
        if ($this->option('daily')) {
            $service->publish(null, true);
        } else {
            $service->retryPending();
        }
        return self::SUCCESS;
    }
}

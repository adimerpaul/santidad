<?php

namespace App\Console\Commands;

use App\Services\PublicidadSyncService;
use Illuminate\Console\Command;

class PublishPublicidad extends Command
{
    protected $signature = 'publicidad:publish-pending';
    protected $description = 'Reintenta programaciones de publicidad pendientes de entregar al socket';

    public function handle(PublicidadSyncService $service): int
    {
        $service->retryPending();
        return self::SUCCESS;
    }
}

<?php

namespace App\Jobs;

use App\Services\WhatsAppSupportBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;

class RespondToWhatsAppSupportMessage implements ShouldQueue, ShouldBeEncrypted
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(private int $caseId, private int $inboundId, private ?string $choice, private ?array $location)
    {
    }

    public function handle(WhatsAppSupportBot $bot): void
    {
        $bot->respond($this->caseId, $this->inboundId, $this->choice, $this->location, true);
    }
}

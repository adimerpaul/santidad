<?php

namespace App\Mail;

use App\Models\Sales;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FacturaEstadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Sales $sale,
        public readonly string $tipo  // 'anulacion' | 'reversion'
    ) {}

    public function build(): static
    {
        $subject = $this->tipo === 'anulacion'
            ? 'Factura Anulada - ' . env('RAZON', 'Santidad Divina')
            : 'Reversión de Anulación - ' . env('RAZON', 'Santidad Divina');

        return $this
            ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
            ->subject($subject)
            ->view('emails.factura_estado')
            ->with([
                'sale' => $this->sale,
                'tipo' => $this->tipo,
            ]);
    }
}

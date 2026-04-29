<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorreoPrueba extends Mailable
{
    use Queueable, SerializesModels;
public string $mensaje;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
    
              public string $subjectText,
        string $mensaje
    ) {
        $this->mensaje = $mensaje;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(
                        config('mail.from.address'),
                        config('mail.from.name')
                    )
                    ->subject($this->subjectText)
                    ->view('prueba')
                    ->with([
                        'mensaje' => $this->mensaje
                    ]);
    
    }
}

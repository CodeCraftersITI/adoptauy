<?php

namespace App\Mail;

use App\Models\Refugio;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Correo al refugio cuando el Administrador lo valida (RF:0005, RF:0063, 13.2.6).
 */
class RefugioDadoDeAlta extends Mailable
{
    public $refugio;

    public function __construct(Refugio $refugio)
    {
        $this->refugio = $refugio;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Tu refugio ya está validado en AdoptaUY');
    }

    public function content(): Content
    {
        return new Content(view: 'correos.refugio-dado-de-alta');
    }
}

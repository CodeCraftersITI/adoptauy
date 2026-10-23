<?php

namespace App\Mail;

use App\Models\Refugio;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Correo al refugio cuando se recibe su solicitud de registro (RF:0034, RF:0063, 13.2.6).
 * Se envía con: Mail::to($correo)->send(new SolicitudRefugioRecibida($refugio));
 * En desarrollo se ve en Mailpit: http://localhost:8025
 */
class SolicitudRefugioRecibida extends Mailable
{
    public $refugio;

    public function __construct(Refugio $refugio)
    {
        $this->refugio = $refugio;
    }

    // Asunto del correo
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recibimos tu solicitud de registro en AdoptaUY');
    }

    // Vista Blade con el texto del correo
    public function content(): Content
    {
        return new Content(view: 'correos.solicitud-refugio-recibida');
    }
}

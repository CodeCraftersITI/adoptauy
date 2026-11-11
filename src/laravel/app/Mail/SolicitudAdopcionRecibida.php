<?php

namespace App\Mail;

use App\Models\SolicitudAdopcion;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Correo al publicante cuando recibe una solicitud de adopción (RF:0049, RF:0063, 13.2.6).
 * Lleva el nombre, el teléfono y el correo del solicitante, que aceptó compartirlos.
 */
class SolicitudAdopcionRecibida extends Mailable
{
    public $solicitud;

    public function __construct(SolicitudAdopcion $solicitud)
    {
        $this->solicitud = $solicitud;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Recibiste una solicitud de adopción en AdoptaUY');
    }

    public function content(): Content
    {
        return new Content(view: 'correos.solicitud-adopcion-recibida');
    }
}

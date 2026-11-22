<?php

namespace App\Mail;

use App\Models\Seguimiento;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

// Aviso de seguimiento del día 15 al adoptante (RF:0055, RF:0063)
class AvisoSeguimiento extends Mailable
{
    public $seguimiento;

    public function __construct(Seguimiento $seguimiento)
    {
        $this->seguimiento = $seguimiento;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Contanos cómo está tu mascota adoptada');
    }

    public function content(): Content
    {
        return new Content(view: 'correos.aviso-seguimiento');
    }
}

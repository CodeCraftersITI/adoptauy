<?php

namespace App\Mail;

use App\Models\Usuario;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Correo para confirmar la dirección del Particular que se registró con el formulario
 * (RF:0043, RF:0063, 13.2.6). Lleva el enlace que arma ConfirmacionCorreoController.
 * Se envía con: Mail::to($correo)->send(new ConfirmarCorreo($usuario, $enlace));
 * En desarrollo se ve en Mailpit: http://localhost:8025
 */
class ConfirmarCorreo extends Mailable
{
    public $usuario;
    public $enlace;

    public function __construct(Usuario $usuario, $enlace)
    {
        $this->usuario = $usuario;
        $this->enlace = $enlace;
    }

    // Asunto del correo
    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Confirmá tu correo en AdoptaUY');
    }

    // Vista Blade con el texto del correo
    public function content(): Content
    {
        return new Content(view: 'correos.confirmar-correo');
    }
}

<?php

namespace App\Console\Commands;

use App\Mail\AvisoSeguimiento;
use App\Models\Seguimiento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

// Tarea diaria de las 03:00 (RF:0055)
class EnviarAvisosSeguimiento extends Command
{
    protected $signature = 'seguimientos:avisar';

    protected $description = 'Envía el aviso de seguimiento del día 15 a los adoptantes (RF:0055)';

    public function handle()
    {
        $seguimientos = Seguimiento::where('Fecha_Aviso', now()->toDateString())
            ->where('Estado_Evidencia', 'Pendiente')
            ->get();

        foreach ($seguimientos as $seguimiento)
        {
            $correo = $seguimiento->adopcion->solicitud->particular->usuario->Email;

            // Si un correo falla, se sigue con los demás (RNF:0010)
            try
            {
                Mail::to($correo)->send(new AvisoSeguimiento($seguimiento));
            }
            catch (\Exception $e)
            {
                report($e);
            }
        }

        $this->info('Avisos de seguimiento enviados: ' . $seguimientos->count());
    }
}

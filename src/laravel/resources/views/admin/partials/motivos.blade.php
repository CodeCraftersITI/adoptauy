{{--
    Lista de reportes pendientes de un objeto: motivo, detalle (si es "Otro"), quién y cuándo.
    Uso: @include('admin.partials.motivos', ['reportes' => $reportes])
--}}
<strong>{{ $reportes->count() }} {{ $reportes->count() == 1 ? 'reporte' : 'reportes' }}</strong>
@foreach ($reportes as $reporte)
    <div class="apagado">
        · {{ $reporte->Motivo }}@if ($reporte->Detalle): «{{ $reporte->Detalle }}»@endif
        ({{ $reporte->reportante->nombreVisible() }}, {{ date('d/m/Y', strtotime($reporte->Fecha_Reporte)) }})
    </div>
@endforeach

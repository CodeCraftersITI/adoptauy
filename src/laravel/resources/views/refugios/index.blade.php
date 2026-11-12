{{--
    Refugios de Montevideo: mapa (RF:0038) y listado de refugios validados (RF:0042).
    $refugios y $marcadores llegan desde RefugioController.
    Si el mapa no carga (sin internet o sin Leaflet), el listado se ve igual (CP-20, RNF:0010).
--}}
@extends('layouts.app')

@section('titulo', 'Refugios')

@section('cabecera')
    @include('partials.leaflet')
@endsection

@section('contenido')
<div class="seccion">
    <div>
        <h1 class="pagina-titulo">Refugios de Montevideo</h1>
        <p class="apagado">Tocá un marcador rosado para ver el perfil del refugio, sus mascotas y cómo donar.</p>
    </div>

    <div class="mapa-caja">
        <div id="mapa" class="mapa" aria-label="Mapa de refugios de Montevideo"></div>
        <div class="mapa-leyenda">
            <span><span class="punto punto-validado"></span>Validado en AdoptaUY</span>
            <span><span class="punto punto-no-registrado"></span>No registrado en AdoptaUY</span>
        </div>
    </div>

    <h2 class="seccion-titulo">Listado de refugios</h2>

    <div class="tarjeta tabla-caja">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Refugio</th>
                    <th>Ubicación</th>
                    <th>Contacto</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($refugios as $refugio)
                    <tr>
                        <td><a href="/refugios/{{ $refugio->ID_Usuario }}"><strong>{{ $refugio->Nombre_Refugio }}</strong></a></td>
                        <td>{{ $refugio->Ubicacion }}</td>
                        <td>
                            @foreach ($refugio->contactos as $contacto)
                                {{ $contacto->Dato_Contacto }}<br>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="vacio">Todavía no hay refugios validados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    // Si Leaflet no cargó (por ejemplo, sin internet), no se dibuja el mapa y el listado sigue ahí
    if (window.L)
    {
        // Mapa centrado en Montevideo, con los mapas de OpenStreetMap y su crédito (obligatorio)
        const mapa = L.map('mapa').setView([-34.88, -56.17], 12);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; colaboradores de <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(mapa);

        // La directiva json de Blade convierte la lista de PHP en una lista de JavaScript
        const marcadores = @json($marcadores);

        // Arma el texto del cartelito de cada marcador. Usa textContent (no innerHTML)
        // para que un nombre con código HTML se muestre como texto y no se ejecute.
        function contenidoDelCartel(marcador)
        {
            const caja = document.createElement('div');

            const nombre = document.createElement('strong');
            nombre.textContent = marcador.nombre;
            caja.appendChild(nombre);

            const direccion = document.createElement('div');
            direccion.textContent = marcador.direccion;
            caja.appendChild(direccion);

            if (marcador.perfil)
            {
                const enlace = document.createElement('a');
                enlace.href = marcador.perfil;
                enlace.textContent = 'Ver perfil y cómo donar';
                caja.appendChild(enlace);
            }
            else
            {
                const aviso = document.createElement('em');
                aviso.textContent = 'No registrado en AdoptaUY';
                caja.appendChild(aviso);
            }

            return caja;
        }

        // Un círculo por refugio: rosado si está validado, blanco si no está registrado
        marcadores.forEach(function (marcador)
        {
            L.circleMarker([marcador.latitud, marcador.longitud], {
                radius: 10,
                color: marcador.perfil ? '#B03A69' : '#9C7A88',
                fillColor: marcador.perfil ? '#B03A69' : '#FFFFFF',
                fillOpacity: 0.9,
                weight: 3,
            }).bindPopup(contenidoDelCartel(marcador)).addTo(mapa);
        });
    }
</script>
@endsection

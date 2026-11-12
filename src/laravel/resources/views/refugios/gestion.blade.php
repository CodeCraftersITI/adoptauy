{{--
    "Mi refugio": ubicación en el mapa (RF:0038) y cuentas bancarias para donaciones (RF:0035 a RF:0037).
    $refugio llega desde MiRefugioController.
--}}
@extends('layouts.app')

@section('titulo', 'Mi refugio')

@section('cabecera')
    @include('partials.leaflet')
@endsection

@section('contenido')
<div class="seccion">
    <a class="detalle-volver" href="/cuenta">← Volver a Mi cuenta</a>

    <div class="seccion-cabecera">
        <div>
            <h1 class="pagina-titulo">Mi refugio</h1>
            <p class="apagado">{{ $refugio->Nombre_Refugio }}</p>
        </div>
        <a class="btn btn-suave" href="/refugios/{{ $refugio->ID_Usuario }}">Ver mi perfil público</a>
    </div>

    {{-- Ubicación: al tocar el mapa se completan los dos campos ocultos --}}
    <h2 class="seccion-titulo">Ubicación en el mapa</h2>
    <form action="/mi-refugio/ubicacion" method="POST" class="tarjeta formulario">
        @csrf
        @method('PUT')

        <p class="apagado">Tocá el mapa en el lugar donde está el refugio y guardá.</p>
        <div id="mapa" class="mapa" aria-label="Mapa para marcar la ubicación del refugio"></div>

        <input type="hidden" name="latitud" id="latitud" value="{{ old('latitud', $refugio->Latitud) }}">
        <input type="hidden" name="longitud" id="longitud" value="{{ old('longitud', $refugio->Longitud) }}">
        @error('latitud') <span class="campo-error">{{ $message }}</span> @enderror

        <button type="submit" class="btn btn-primario">Guardar ubicación</button>
    </form>

    {{-- Cuentas bancarias (RF:0035 a RF:0037) --}}
    <h2 class="seccion-titulo">Cuentas para donaciones</h2>

    <div class="tarjeta tabla-caja">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Banco</th>
                    <th>Tipo de cuenta</th>
                    <th>Número</th>
                    <th>Titular</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($refugio->cuentas as $cuenta)
                    <tr>
                        <td>{{ $cuenta->Banco }}</td>
                        <td>{{ $cuenta->Tipo_Cuenta }}</td>
                        <td>{{ $cuenta->Numero_Cuenta }}</td>
                        <td>{{ $cuenta->Titular }}</td>
                        <td>
                            <form action="/mi-refugio/cuentas/{{ $cuenta->ID_Cuenta_Banco }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-peligro btn-chico">Quitar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="vacio">Todavía no cargaste cuentas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form action="/mi-refugio/cuentas" method="POST" class="tarjeta formulario formulario-grilla">
        @csrf

        <label class="campo">
            Banco
            <input class="entrada" type="text" name="banco" value="{{ old('banco') }}" maxlength="60" required>
            @error('banco') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Tipo de cuenta
            <input class="entrada" type="text" name="tipo_cuenta" value="{{ old('tipo_cuenta') }}" placeholder="Caja de ahorro, cuenta corriente..." maxlength="60" required>
            @error('tipo_cuenta') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Número de cuenta
            <input class="entrada" type="text" name="numero_cuenta" value="{{ old('numero_cuenta') }}" maxlength="30" required>
            @error('numero_cuenta') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Titular
            <input class="entrada" type="text" name="titular" value="{{ old('titular') }}" maxlength="120" required>
            @error('titular') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="btn btn-primario ocupa-todo">Agregar cuenta</button>
    </form>
</div>

<script>
    if (window.L)
    {
        const campoLatitud = document.getElementById('latitud');
        const campoLongitud = document.getElementById('longitud');

        const mapa = L.map('mapa').setView([-34.88, -56.17], 12);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; colaboradores de <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(mapa);

        // Si ya tenía ubicación, se muestra el marcador ahí
        let marcador = null;
        if (campoLatitud.value && campoLongitud.value)
        {
            marcador = L.marker([campoLatitud.value, campoLongitud.value]).addTo(mapa);
            mapa.setView([campoLatitud.value, campoLongitud.value], 14);
        }

        // Al tocar el mapa se mueve el marcador y se completan los campos ocultos
        mapa.on('click', function (evento)
        {
            if (marcador)
            {
                marcador.setLatLng(evento.latlng);
            }
            else
            {
                marcador = L.marker(evento.latlng).addTo(mapa);
            }

            campoLatitud.value = evento.latlng.lat.toFixed(6);
            campoLongitud.value = evento.latlng.lng.toFixed(6);
        });
    }
</script>
@endsection

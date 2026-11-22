{{--
    El Administrador modifica los atributos de un Particular (RF:0013): cédula, nombre, apellido
    y teléfono. Los tres últimos salen del mismo partial que "Mi perfil".
    $particular llega desde AdminParticularController. El correo es de la cuenta y no se cambia acá.
--}}
@extends('layouts.app')

@section('titulo', 'Editar particular · Administración')

@section('contenido')
<div class="formulario-angosto formulario-ancho">
    <a class="detalle-volver" href="/admin/particulares">← Volver a Particulares</a>

    <h1 class="pagina-titulo">Editar particular</h1>

    <form action="/admin/particulares/{{ $particular->ID_Usuario }}" method="POST" class="tarjeta formulario formulario-grilla">
        @csrf
        @method('PUT')

        <dl class="datos ocupa-todo">
            <div><dt>Correo</dt><dd>{{ $particular->usuario->Email }}</dd></div>
            <div><dt>Estado de la cuenta</dt><dd>{{ $particular->usuario->Estado_Usuario }}</dd></div>
        </dl>

        {{-- La cédula no se muestra a otros usuarios (RNF:0013): el campo viene vacío y solo se
             usa si el Administrador escribe una nueva para corregirla --}}
        <label class="campo">
            Cédula nueva <span class="campo-ayuda">(opcional)</span>
            <input class="entrada solo-numeros" type="text" name="ci" value="{{ old('ci') }}" inputmode="numeric" maxlength="8" placeholder="Dejala vacía para no cambiarla">
            @error('ci') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        @include('partials.campos-particular', ['particular' => $particular])

        <button type="submit" class="btn btn-primario ocupa-todo">Guardar cambios</button>
    </form>
</div>
@endsection

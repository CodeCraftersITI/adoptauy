{{--
    Nombre, ubicación y datos de contacto de un Refugio, con sus datos cargados.
    Lo usan "Mi perfil" (RF:0029) y el Administrador al editar un refugio (RF:0007).
    Uso: @include('partials.campos-refugio', ['refugio' => $refugio])
--}}
<label class="campo">
    Nombre del refugio
    <input class="entrada" type="text" name="nombre_refugio" value="{{ old('nombre_refugio', $refugio->Nombre_Refugio) }}" maxlength="100" required>
    @error('nombre_refugio') <span class="campo-error">{{ $message }}</span> @enderror
</label>

<label class="campo">
    Ubicación
    <input class="entrada" type="text" name="ubicacion" value="{{ old('ubicacion', $refugio->Ubicacion) }}" maxlength="255" required>
    @error('ubicacion') <span class="campo-error">{{ $message }}</span> @enderror
</label>

<label class="campo ocupa-todo">
    Datos de contacto
    <textarea class="entrada" name="contactos" rows="3" required>{{ old('contactos', $refugio->contactos->pluck('Dato_Contacto')->implode("\n")) }}</textarea>
    <span class="campo-ayuda">Uno por línea: teléfono, correo o red social. Se ven en el perfil público.</span>
    @error('lista_contactos') <span class="campo-error">{{ $message }}</span> @enderror
    @error('lista_contactos.*') <span class="campo-error">{{ $message }}</span> @enderror
</label>

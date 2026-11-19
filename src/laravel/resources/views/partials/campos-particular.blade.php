{{--
    Nombre, apellido y teléfono de un Particular, con sus datos cargados.
    Lo usan "Mi perfil" (RF:0016) y el Administrador al editar un particular (RF:0013).
    Uso: @include('partials.campos-particular', ['particular' => $particular])
--}}
<label class="campo">
    Nombre
    <input class="entrada" type="text" name="nombre" value="{{ old('nombre', $particular->Nombre) }}" maxlength="50" required>
    @error('nombre') <span class="campo-error">{{ $message }}</span> @enderror
</label>

<label class="campo">
    Apellido
    <input class="entrada" type="text" name="apellido" value="{{ old('apellido', $particular->Apellido) }}" maxlength="50" required>
    @error('apellido') <span class="campo-error">{{ $message }}</span> @enderror
</label>

<label class="campo">
    Teléfono
    <input class="entrada solo-numeros" type="text" name="telefono" value="{{ old('telefono', $particular->Telefono) }}" inputmode="numeric" maxlength="9" required>
    @error('telefono') <span class="campo-error">{{ $message }}</span> @enderror
</label>

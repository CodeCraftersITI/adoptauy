{{--
    Campos comunes a los dos formularios de publicación: especie, raza y sexo.
    Se incluye desde crear-adopcion y crear-perdida.
    old('campo') vuelve a marcar lo que el usuario eligió si hubo un error.
--}}
<label class="campo">
    Especie
    <select class="entrada" name="especie" required>
        <option value="">Elegí una opción</option>
        <option value="Perro" {{ old('especie') == 'Perro' ? 'selected' : '' }}>Perro</option>
        <option value="Gato" {{ old('especie') == 'Gato' ? 'selected' : '' }}>Gato</option>
    </select>
    @error('especie') <span class="campo-error">{{ $message }}</span> @enderror
</label>

<label class="campo">
    Raza
    <input class="entrada" type="text" name="raza" value="{{ old('raza') }}" placeholder="Ej.: mestizo" required>
    @error('raza') <span class="campo-error">{{ $message }}</span> @enderror
</label>

<label class="campo">
    Sexo
    <select class="entrada" name="sexo" required>
        <option value="">Elegí una opción</option>
        <option value="Hembra" {{ old('sexo') == 'Hembra' ? 'selected' : '' }}>Hembra</option>
        <option value="Macho" {{ old('sexo') == 'Macho' ? 'selected' : '' }}>Macho</option>
    </select>
    @error('sexo') <span class="campo-error">{{ $message }}</span> @enderror
</label>

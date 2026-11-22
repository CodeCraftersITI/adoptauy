{{--
    Cédula y teléfono del Particular (13.2.1). Se incluye desde el registro y desde
    "Completar datos" de Google, que piden lo mismo.
    - inputmode="numeric": el celular muestra el teclado de números.
    - maxlength: no deja escribir más dígitos de los permitidos (CI 8, teléfono 9).
    - class="solo-numeros": el script del layout borra lo que no sea un número mientras se escribe.
    Se usa type="text" y no type="number" porque este último acepta "e", "+" y "-",
    ignora maxlength y muestra flechitas para subir y bajar el valor.
    El servidor igual lo valida todo (CedulaUruguaya y regex del teléfono).
--}}
<label class="campo">
    Cédula de identidad
    <input class="entrada solo-numeros" type="text" name="ci" value="{{ old('ci') }}"
           inputmode="numeric" maxlength="8" placeholder="Solo números, sin puntos ni guion" required>
    @error('ci') <span class="campo-error">{{ $message }}</span> @enderror
</label>

<label class="campo">
    Teléfono
    <input class="entrada solo-numeros" type="text" name="telefono" value="{{ old('telefono') }}"
           inputmode="numeric" maxlength="9" placeholder="Ej.: 099123456" required>
    @error('telefono') <span class="campo-error">{{ $message }}</span> @enderror
</label>

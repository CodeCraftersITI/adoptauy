{{--
    Mensajes para el usuario (se incluye desde layouts.app).
    Un controlador los manda con: return redirect('/...')->with('exito', 'Texto');
                              o:  return back()->with('error', 'Texto');
--}}
@if (session('exito'))
    <div class="mensaje mensaje-exito">{{ session('exito') }}</div>
@endif

@if (session('error'))
    <div class="mensaje mensaje-error">{{ session('error') }}</div>
@endif

{{-- Errores de $request->validate(): el detalle aparece debajo de cada campo --}}
@if ($errors->any())
    <div class="mensaje mensaje-error">Revisá los datos marcados en el formulario.</div>
@endif

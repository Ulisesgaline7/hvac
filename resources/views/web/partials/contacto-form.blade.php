@if(session('ok'))
    <div class="ok" role="status">{{ session('ok') }}</div>
@endif
@if($errors->any())
    <div class="err" role="alert">
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('contacto.enviar') }}" class="card">
    @csrf
    <input type="text" name="sitio_web" class="trap" tabindex="-1" autocomplete="off" aria-hidden="true">
    <div class="row2">
        <div class="fld"><label for="nombre">Nombre</label><input id="nombre" name="nombre" value="{{ old('nombre') }}" autocomplete="name" required></div>
        <div class="fld"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" type="tel" value="{{ old('telefono') }}" autocomplete="tel" required></div>
    </div>
    <div class="row2">
        <div class="fld"><label for="email">Correo (opcional)</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email"></div>
        <div class="fld"><label for="ciudad">Ciudad</label><input id="ciudad" name="ciudad" value="{{ old('ciudad') }}"></div>
    </div>
    <div class="fld">
        <label for="servicio">¿Qué necesitas?</label>
        <select id="servicio" name="servicio">
            @foreach(['Aire acondicionado','Refrigeración comercial o industrial','Ventilación','Mantenimiento y contratos','Reparación o emergencia','Otro'] as $s)
                <option @selected(old('servicio') === $s)>{{ $s }}</option>
            @endforeach
        </select>
    </div>
    <div class="fld"><label for="mensaje">Mensaje</label><textarea id="mensaje" name="mensaje" required>{{ old('mensaje') }}</textarea></div>
    <button class="btn btn-n" type="submit">Enviar mensaje</button>
</form>

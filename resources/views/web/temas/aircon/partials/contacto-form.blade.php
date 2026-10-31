@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('contacto.enviar') }}">
    @csrf
    <input type="text" name="sitio_web" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0">
    <div class="row g-3">
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Tu nombre" value="{{ old('nombre') }}" autocomplete="name" required>
                <label for="nombre">Tu nombre</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating">
                <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="Teléfono" value="{{ old('telefono') }}" autocomplete="tel" required>
                <label for="telefono">Teléfono</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="Correo (opcional)" value="{{ old('email') }}" autocomplete="email">
                <label for="email">Correo (opcional)</label>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="ciudad" name="ciudad" placeholder="Ciudad" value="{{ old('ciudad') }}">
                <label for="ciudad">Ciudad</label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-floating">
                <select class="form-select" id="servicio" name="servicio">
                    @foreach(['Aire acondicionado', 'Refrigeración comercial o industrial', 'Ventilación', 'Mantenimiento y contratos', 'Reparación o emergencia', 'Otro'] as $s)
                        <option @selected(old('servicio') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
                <label for="servicio">¿Qué necesitas?</label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-floating">
                <textarea class="form-control" placeholder="Mensaje" id="mensaje" name="mensaje" style="height: 120px" required>{{ old('mensaje') }}</textarea>
                <label for="mensaje">Mensaje</label>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-primary py-3 px-5" type="submit">Enviar mensaje</button>
        </div>
    </div>
</form>

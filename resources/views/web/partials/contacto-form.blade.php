<section id="contacto" class="py-5">
  <div class="container">
    <h2 class="mb-2">Solicita tu cotización</h2>
    <p class="mb-4">Cuéntanos qué necesitas y te respondemos lo antes posible.</p>

    @if(session('ok'))
      <div class="alert alert-success">{{ session('ok') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    @endif

    <form method="POST" action="{{ route('contacto.enviar') }}" class="row g-3">
      @csrf
      <input type="text" name="sitio_web" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px">
      <div class="col-md-6"><label class="form-label">Nombre</label><input name="nombre" class="form-control" value="{{ old('nombre') }}" required></div>
      <div class="col-md-6"><label class="form-label">Teléfono</label><input name="telefono" class="form-control" value="{{ old('telefono') }}" required></div>
      <div class="col-md-6"><label class="form-label">Correo (opcional)</label><input type="email" name="email" class="form-control" value="{{ old('email') }}"></div>
      <div class="col-md-6"><label class="form-label">Ciudad</label><input name="ciudad" class="form-control" value="{{ old('ciudad') }}"></div>
      <div class="col-12"><label class="form-label">Servicio</label>
        <select name="servicio" class="form-select">
          <option>Aire acondicionado</option>
          <option>Refrigeración</option>
          <option>Ventilación</option>
          <option>Mantenimiento</option>
          <option>Cotización</option>
        </select>
      </div>
      <div class="col-12"><label class="form-label">Mensaje</label><textarea name="mensaje" rows="4" class="form-control" required>{{ old('mensaje') }}</textarea></div>
      <div class="col-12"><button class="btn btn-primary">Enviar mensaje</button></div>
    </form>
  </div>
</section>

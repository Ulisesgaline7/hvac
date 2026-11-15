@auth
@if (! auth()->user()->cliente_id)
@php
$divs = \App\Models\Division::activas()->orderBy('id')->pluck('nombre', 'id');
$actual = (int) session('division_id', 1);
@endphp
<form method="POST" action="{{ route('admin.cambiar-division') }}">
@csrf
<label for="division_id">Divisi&oacute;n:</label>
<select id="division_id" name="division_id" onchange="this.form.submit()">
@foreach ($divs as $id => $nombre)
<option value="{{ $id }}" @selected($id === $actual)>{{ $nombre }}</option>
@endforeach
</select>
</form>
@endif
@endauth

@if (session()->has('suplantador_id'))
<form method="POST" action="{{ route('suplantar.volver') }}" style="background:#f59e0b;color:#000;padding:.5rem 1rem;border-radius:.5rem;margin-bottom:1rem;display:flex;gap:1rem;align-items:center;">
@csrf
<span>Est&aacute;s viendo el sistema como {{ auth()->user()->name }}.</span>
<button type="submit" style="font-weight:600;text-decoration:underline;">Volver a mi cuenta</button>
</form>
@endif

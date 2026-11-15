<form method="POST" action="{{ route('admin.cambiar-division') }}" class="flex items-center gap-2 me-3">
    @csrf
    <select name="division_id" onchange="this.form.submit()"
        class="fi-input rounded-lg border-none bg-white py-1.5 ps-3 pe-8 text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:text-white dark:ring-white/20">
        @foreach ($divisiones as $d)
            <option value="{{ $d->id }}" @selected($d->id === $actual)>{{ $d->codigo }}</option>
        @endforeach
    </select>
</form>

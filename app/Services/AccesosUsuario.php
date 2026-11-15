<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AccesosCuenta;
use Filament\Facades\Filament;
use Illuminate\Support\Str;

class AccesosUsuario
{
    public function crear(array $d, string $rol): User
    {
        $pass = Str::password(12, symbols: false);

        $u = new User;
        $u->forceFill([
            'name' => $d['name'],
            'email' => $d['email'],
            'cargo' => $d['cargo'] ?? null,
            'cliente_id' => in_array($rol, ['cliente_admin', 'cliente_consulta']) ? ($d['cliente_id'] ?? null) : null,
            'password' => $pass,
            'debe_cambiar_password' => true,
        ])->save();
        $u->assignRole($rol);

        $this->enviar($u, $pass, false);

        return $u;
    }

    public function reenviar(User $u): void
    {
        $pass = Str::password(12, symbols: false);
        $u->forceFill(['password' => $pass, 'debe_cambiar_password' => true])->save();
        $this->enviar($u, $pass, true);
    }

    private function enviar(User $u, string $pass, bool $reenvio): void
    {
        $panel = match (true) {
            $u->hasAnyRole(['cliente_admin', 'cliente_consulta']) => 'portal',
            $u->hasRole('tecnico') => 'tecnico',
            default => 'admin',
        };

        $u->notify(new AccesosCuenta($pass, Filament::getPanel($panel)->getUrl(), $reenvio));
    }
}

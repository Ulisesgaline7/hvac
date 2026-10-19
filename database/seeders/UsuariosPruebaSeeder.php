<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\Cliente;
use App\Models\Tecnico;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosPruebaSeeder extends Seeder
{
    private const CLAVE = 'Prueba12345!';

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('UsuariosPruebaSeeder solo corre en entorno local.');

            return;
        }

        $this->call(RolesSeeder::class);

        $clienteA = Cliente::firstOrCreate(['razon_social' => 'Restaurante Demo']);
        $clienteB = Cliente::firstOrCreate(['razon_social' => 'Supermercado Demo B']);

        $this->usuario('Coordinador Prueba', 'coordinador@prueba.test', Rol::Coordinador);

        $tecnico2 = $this->usuario('Técnico Prueba 2', 'tecnico2@prueba.test', Rol::Tecnico);
        Tecnico::firstOrCreate(['user_id' => $tecnico2->id], ['estado_homologacion' => 'aprobado']);

        $this->usuario('Cliente Admin A', 'cliente.admin.a@prueba.test', Rol::ClienteAdmin, $clienteA->id);
        $this->usuario('Cliente Consulta A', 'cliente.consulta.a@prueba.test', Rol::ClienteConsulta, $clienteA->id);
        $this->usuario('Cliente Admin B', 'cliente.admin.b@prueba.test', Rol::ClienteAdmin, $clienteB->id);
    }

    private function usuario(string $nombre, string $correo, Rol $rol, ?int $clienteId = null): User
    {
        $user = User::where('email', $correo)->first();

        if (! $user) {
            $user = new User();
            $user->forceFill([
                'name' => $nombre,
                'email' => $correo,
                'password' => Hash::make(self::CLAVE),
                'cliente_id' => $clienteId,
            ])->save();
        }

        $user->syncRoles([$rol->value]);

        return $user;
    }
}

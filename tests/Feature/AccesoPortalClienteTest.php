<?php

use App\Filament\Resources\Clientes\Pages\EditCliente;
use App\Models\Cliente;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole('coordinador'));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('el coordinador crea un acceso ligado al cliente de la ficha', function () {
    $c = Cliente::create(['razon_social' => 'Cliente Acceso']);

    Livewire::test(EditCliente::class, ['record' => $c->getKey()])
        ->callAction('crearAccesoPortal', [
            'name' => 'Ana Gómez', 'email' => 'ana@cliente.test', 'password' => 'clave12345', 'rol' => 'cliente_admin',
        ])
        ->assertHasNoActionErrors();

    $u = User::where('email', 'ana@cliente.test')->first();
    expect($u)->not->toBeNull()
        ->and($u->cliente_id)->toBe($c->id)
        ->and($u->hasRole('cliente_admin'))->toBeTrue()
        ->and($u->hasAnyRole(['administrador', 'coordinador']))->toBeFalse();
});

it('no permite repetir un correo existente', function () {
    $c = Cliente::create(['razon_social' => 'Cliente Acceso']);
    User::factory()->create(['email' => 'repetido@cliente.test']);

    Livewire::test(EditCliente::class, ['record' => $c->getKey()])
        ->callAction('crearAccesoPortal', [
            'name' => 'X', 'email' => 'repetido@cliente.test', 'password' => 'clave12345', 'rol' => 'cliente_consulta',
        ])
        ->assertHasActionErrors(['email']);
});

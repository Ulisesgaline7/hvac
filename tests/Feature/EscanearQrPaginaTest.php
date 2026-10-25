<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesSeeder::class));

it('el técnico abre la página de escaneo', function () {
    $u = User::factory()->create();
    $u->assignRole('tecnico');

    $this->actingAs($u)->get('/tecnico/escanear')->assertOk()->assertSee('Abrir cámara');
});

it('sin sesión la página de escaneo redirige al login', function () {
    $this->get('/tecnico/escanear')->assertRedirect();
});

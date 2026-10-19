<?php

use App\Filament\Resources\OrdenTrabajos\Pages\CreateOrdenTrabajo;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    $this->actingAs(User::factory()->create()->assignRole('administrador'));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('instalación oculta equipos, contrato y origen', function () {
    Livewire::test(CreateOrdenTrabajo::class)
        ->fillForm(['tipo' => 'instalacion'])
        ->assertFormFieldHidden('equipos')
        ->assertFormFieldHidden('contrato_id')
        ->assertFormFieldHidden('origen_aviso')
        ->assertFormFieldVisible('precio_estimado')
        ->assertFormFieldVisible('anticipo');
});

it('visita técnica oculta contrato, precio y anticipo', function () {
    Livewire::test(CreateOrdenTrabajo::class)
        ->fillForm(['tipo' => 'visita_tecnica'])
        ->assertFormFieldHidden('contrato_id')
        ->assertFormFieldHidden('precio_estimado')
        ->assertFormFieldHidden('anticipo')
        ->assertFormFieldVisible('visita_cobro');
});

it('emergencia fija prioridad y oculta anticipo', function () {
    Livewire::test(CreateOrdenTrabajo::class)
        ->fillForm(['tipo' => 'emergencia'])
        ->assertFormSet(['prioridad' => 'emergencia'])
        ->assertFormFieldHidden('anticipo');
});

it('correctivo exige descripción', function () {
    Livewire::test(CreateOrdenTrabajo::class)
        ->fillForm(['tipo' => 'correctivo', 'descripcion' => null])
        ->call('create')
        ->assertHasFormErrors(['descripcion' => 'required']);
});

<?php

use App\Filament\Resources\Contratos\Pages\EditContrato;
use App\Models\Contrato;
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

it('el coordinador captura cuota y periodicidad del contrato', function () {
    $ot = otListaParaCerrar();
    $c = Contrato::create([
        'cliente_id' => $ot->cliente_id, 'tipo' => 'preventivo_recurrente', 'estado' => 'borrador',
        'vigencia_inicio' => '2026-01-01', 'vigencia_fin' => '2026-12-31',
    ]);

    Livewire::test(EditContrato::class, ['record' => $c->getKey()])
        ->fillForm(['cuota_monto' => 2500, 'periodicidad' => 'trimestral'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect((float) $c->fresh()->cuota_monto)->toBe(2500.0)
        ->and($c->fresh()->periodicidad)->toBe('trimestral');
});

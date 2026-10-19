<?php

use App\Filament\Resources\OrdenTrabajos\Pages\EditOrdenTrabajo;
use App\Models\Pago;
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

it('registrar anticipo desde la OT crea el pago ligado a la OT', function () {
    $ot = otListaParaCerrar();

    Livewire::test(EditOrdenTrabajo::class, ['record' => $ot->getKey()])
        ->callAction('registrarAnticipo', ['monto' => 300, 'metodo' => 'efectivo']);

    $p = Pago::where('orden_trabajo_id', $ot->id)->first();
    expect($p)->not->toBeNull()
        ->and($p->tipo)->toBe('anticipo')
        ->and((float) $p->monto)->toBe(300.0)
        ->and($p->factura_id)->toBeNull();
});

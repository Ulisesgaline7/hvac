<?php

use App\Filament\Resources\Facturas\Pages\ListFacturas;
use App\Filament\Resources\Facturas\Pages\ViewFactura;
use App\Models\User;
use App\Services\FacturacionService;
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

function facturaDePrueba()
{
    $ot = otListaParaCerrar();
    $ot->update(['precio_estimado' => 1000]);

    return app(FacturacionService::class)->generarDesdeOt($ot->fresh());
}

it('la lista de facturas muestra la factura', function () {
    $f = facturaDePrueba();

    Livewire::test(ListFacturas::class)->assertCanSeeTableRecords([$f]);
});

it('registrar un pago parcial deja la factura parcial', function () {
    $f = facturaDePrueba();

    Livewire::test(ViewFactura::class, ['record' => $f->getKey()])
        ->callAction('registrarPago', ['monto' => 500, 'fecha' => now()->toDateString(), 'metodo' => 'efectivo']);

    expect($f->fresh()->estado)->toBe('parcial')->and((float) $f->fresh()->pagado)->toBe(500.0);
});

it('no deja pagar más que el saldo', function () {
    $f = facturaDePrueba();

    Livewire::test(ViewFactura::class, ['record' => $f->getKey()])
        ->callAction('registrarPago', ['monto' => 99999, 'fecha' => now()->toDateString(), 'metodo' => 'efectivo'])
        ->assertHasActionErrors();

    expect((float) $f->fresh()->pagado)->toBe(0.0);
});

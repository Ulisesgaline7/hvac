<?php

use App\Filament\Portal\Resources\Facturas\Pages\ListFacturasPortal;
use App\Models\Cliente;
use App\Models\Factura;
use App\Models\User;
use App\Services\FacturacionService;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('portal'));
});

it('el cliente ve sus facturas y no las de otro cliente', function () {
    $ot = otListaParaCerrar();
    $ot->update(['precio_estimado' => 1000]);
    $mia = app(FacturacionService::class)->generarDesdeOt($ot->fresh());

    $otro = Cliente::create(['razon_social' => 'Otro cliente']);
    $ajena = Factura::create([
        'cliente_id' => $otro->id, 'fecha' => now()->toDateString(), 'vence' => now()->toDateString(),
        'subtotal' => 100, 'isv' => 15, 'total' => 115,
    ]);

    $u = User::factory()->create(['cliente_id' => $ot->cliente_id])->assignRole('cliente_admin');
    $this->actingAs($u);

    Livewire::test(ListFacturasPortal::class)
        ->assertCanSeeTableRecords([$mia])
        ->assertCanNotSeeTableRecords([$ajena]);
});

it('un usuario sin cliente no ve ninguna factura', function () {
    $ot = otListaParaCerrar();
    $ot->update(['precio_estimado' => 1000]);
    $f = app(FacturacionService::class)->generarDesdeOt($ot->fresh());

    $this->actingAs(User::factory()->create(['cliente_id' => null])->assignRole('cliente_admin'));

    Livewire::test(ListFacturasPortal::class)->assertCanNotSeeTableRecords([$f]);
});

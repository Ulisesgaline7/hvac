<?php

use App\Filament\Portal\Resources\Cotizaciones\Pages\ListCotizacionesPortal;
use App\Filament\Portal\Resources\OrdenesTrabajo\Pages\ListOrdenesTrabajoPortal;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesSeeder::class);
    Filament::setCurrentPanel(Filament::getPanel('portal'));
});

it('el cliente ve sus cotizaciones enviadas y aprobadas, no borradores ni ajenas', function () {
    $ot = otListaParaCerrar();
    $otro = Cliente::create(['razon_social' => 'Otro cliente']);

    $enviada = Cotizacion::create(['cliente_id' => $ot->cliente_id, 'estado' => 'enviada']);
    $aprobada = Cotizacion::create(['cliente_id' => $ot->cliente_id, 'estado' => 'aprobada']);
    $borrador = Cotizacion::create(['cliente_id' => $ot->cliente_id, 'estado' => 'borrador']);
    $ajena = Cotizacion::create(['cliente_id' => $otro->id, 'estado' => 'enviada']);

    $this->actingAs(User::factory()->create(['cliente_id' => $ot->cliente_id])->assignRole('cliente_admin'));

    Livewire::test(ListCotizacionesPortal::class)
        ->assertCanSeeTableRecords([$enviada, $aprobada])
        ->assertCanNotSeeTableRecords([$borrador, $ajena]);
});

it('el cliente ve sus OT y otro cliente no las ve', function () {
    $ot = otListaParaCerrar();
    $otro = Cliente::create(['razon_social' => 'Otro cliente']);

    $this->actingAs(User::factory()->create(['cliente_id' => $ot->cliente_id])->assignRole('cliente_admin'));
    Livewire::test(ListOrdenesTrabajoPortal::class)->assertCanSeeTableRecords([$ot]);

    $this->actingAs(User::factory()->create(['cliente_id' => $otro->id])->assignRole('cliente_admin'));
    Livewire::test(ListOrdenesTrabajoPortal::class)->assertCanNotSeeTableRecords([$ot]);
});

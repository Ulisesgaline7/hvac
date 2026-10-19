<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('un usuario de cliente entra al portal y no al admin', function () {
    $ot = otListaParaCerrar();
    $u = User::factory()->create(['cliente_id' => $ot->cliente_id])->assignRole('cliente_admin');

    expect($u->canAccessPanel(Filament::getPanel('portal')))->toBeTrue()
        ->and($u->canAccessPanel(Filament::getPanel('admin')))->toBeFalse()
        ->and($u->canAccessPanel(Filament::getPanel('tecnico')))->toBeFalse();
});

it('un coordinador no entra al portal', function () {
    $u = User::factory()->create()->assignRole('coordinador');

    expect($u->canAccessPanel(Filament::getPanel('portal')))->toBeFalse();
});

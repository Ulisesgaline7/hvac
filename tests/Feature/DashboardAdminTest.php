<?php

use App\Filament\Widgets\ResumenAdminWidget;
use App\Filament\Widgets\ResumenOperativoWidget;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function usuarioDashboard(string $rol): User
{
    test()->seed(RolesSeeder::class);
    $u = User::factory()->create();
    $u->assignRole($rol);

    return $u;
}

it('el administrador ve los dos widgets', function () {
    test()->actingAs(usuarioDashboard('administrador'));

    expect(ResumenOperativoWidget::canView())->toBeTrue()
        ->and(ResumenAdminWidget::canView())->toBeTrue();
});

it('el coordinador ve solo el widget operativo', function () {
    test()->actingAs(usuarioDashboard('coordinador'));

    expect(ResumenOperativoWidget::canView())->toBeTrue()
        ->and(ResumenAdminWidget::canView())->toBeFalse();
});

it('técnico y cliente no ven los widgets del panel admin', function () {
    test()->actingAs(usuarioDashboard('tecnico'));
    expect(ResumenOperativoWidget::canView())->toBeFalse();

    test()->actingAs(usuarioDashboard('cliente_admin'));
    expect(ResumenOperativoWidget::canView())->toBeFalse()
        ->and(ResumenAdminWidget::canView())->toBeFalse();
});

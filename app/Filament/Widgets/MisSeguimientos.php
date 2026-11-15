<?php

namespace App\Filament\Widgets;

use App\Models\OportunidadActividad;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class MisSeguimientos extends TableWidget
{
    protected static ?string $heading = 'Mis seguimientos (hoy y vencidos)';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $u = auth()->user();

        return $u && $u->hasAnyRole(['administrador', 'coordinador', 'comercial']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OportunidadActividad::query()
                    ->where('responsable_id', auth()->id())
                    ->whereNull('hecha_en')
                    ->whereNotNull('vence_en')
                    ->where('vence_en', '<=', now()->endOfDay())
                    ->orderBy('vence_en')
            )
            ->columns([
                TextColumn::make('vence_en')->label('Vence')->dateTime('d/m H:i'),
                TextColumn::make('tipo')->badge(),
                TextColumn::make('oportunidad.titulo')->label('Oportunidad'),
                TextColumn::make('descripcion')->label('Detalle')->limit(60),
            ])
            ->paginated(false);
    }
}

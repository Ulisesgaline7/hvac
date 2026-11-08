<?php

namespace App\Filament\Resources\Equipos\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/** Documentos escaneados del equipo. Los sube el coordinador/administrador; técnicos y clientes los ven según RLS. */
class DocumentosEquipoRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Documentos del equipo';

    public function isReadOnly(): bool { return false; }

    public const TIPOS = [
        'manual' => 'Manual',
        'ficha_fabricante' => 'Ficha del fabricante',
        'garantia_fabricante' => 'Garantía del fabricante',
        'otro' => 'Otro',
    ];

    private static function interno(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrador', 'coordinador']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Documento'),
                TextColumn::make('tipo')->label('Tipo')
                    ->state(fn ($record) => self::TIPOS[$record->getCustomProperty('tipo')] ?? 'Otro'),
                TextColumn::make('size')->label('Tamaño')
                    ->state(fn ($record) => number_format($record->size / 1024, 0) . ' KB'),
                TextColumn::make('created_at')->label('Subido')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([
                Action::make('subir')
                    ->label('Subir documento')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn () => self::interno())
                    ->modalHeading('Subir documento escaneado')
                    ->schema([
                        Select::make('tipo')->label('Tipo')->options(self::TIPOS)->required()->default('manual'),
                        TextInput::make('nombre')->label('Nombre o descripción')->required()->maxLength(150),
                        FileUpload::make('archivo')
                            ->label('Archivo (PDF, JPG o PNG, máx. 15 MB)')
                            ->disk('local')
                            ->directory('tmp-documentos-equipo')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(15360)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        abort_unless(self::interno(), 403);

                        $ruta = \Illuminate\Support\Facades\Storage::disk('local')->path($data['archivo']);
                        $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));

                        $this->getOwnerRecord()
                            ->addMedia($ruta)
                            ->usingName($data['nombre'])
                            ->usingFileName(Str::slug($data['nombre']) . '-' . Str::lower(Str::random(6)) . '.' . $ext)
                            ->withCustomProperties(['tipo' => $data['tipo']])
                            ->toMediaCollection('documentos_equipo');

                        Notification::make()->success()->title('Documento subido')->send();
                    }),
            ])
            ->recordActions([
                Action::make('descargar')
                    ->label('Ver / descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => route('equipos.documento', $record->id), shouldOpenInNewTab: true),
                DeleteAction::make()->visible(fn () => self::interno()),
            ])
            ->emptyStateHeading('Sin documentos escaneados');
    }
}

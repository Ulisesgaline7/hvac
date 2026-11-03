<?php

namespace App\Filament\Pages;

use App\Support\DocumentoMarca;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Documentos extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static string|UnitEnum|null $navigationGroup = 'Configuración';
    protected static ?string $navigationLabel = 'Documentos';
    protected static ?string $slug = 'documentos';
    protected static ?string $title = 'Documentos y plantillas';
    protected string $view = 'filament.pages.documentos';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole('administrador');
    }

    public function mount(): void
    {
        $this->form->fill(DocumentoMarca::todo());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Marca')->columns(2)->schema([
                FileUpload::make('logo')->label('Logo (PNG o JPG)')->disk('public')->directory('documentos')
                    ->acceptedFileTypes(['image/png', 'image/jpeg'])->image()->maxSize(1024)->columnSpanFull(),
                ColorPicker::make('color_primario')->label('Color principal'),
                ColorPicker::make('color_oscuro')->label('Color de títulos y tablas'),
                ColorPicker::make('color_acento')->label('Color de acento'),
            ]),
            Section::make('Datos de la empresa')->columns(2)->schema([
                TextInput::make('empresa_nombre')->label('Nombre')->required(),
                TextInput::make('empresa_rtn')->label('RTN'),
                TextInput::make('empresa_telefono')->label('Teléfono'),
                TextInput::make('empresa_correo')->label('Correo'),
                TextInput::make('empresa_direccion')->label('Dirección')->columnSpanFull(),
                TextInput::make('pie_texto')->label('Texto del pie')->columnSpanFull(),
            ]),
        ]);
    }

    public function guardar(): void
    {
        DocumentoMarca::guardar($this->form->getState());
        Notification::make()->title('Guardado. Abre una vista previa para verlo.')->success()->send();
    }

    public function documentos(): array
    {
        return [
            ['tipo' => 'cotizacion', 'nombre' => 'Cotización'],
            ['tipo' => 'factura', 'nombre' => 'Factura'],
            ['tipo' => 'recibo', 'nombre' => 'Recibo de pago'],
        ];
    }
}

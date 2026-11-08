<?php

namespace App\Filament\Resources\OrdenTrabajos\RelationManagers;

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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Documentos escaneados de la OT. Los suben el equipo asignado (incluido el supervisor) y el personal interno; el cliente los ve con la OT cerrada. */
class DocumentosOtRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Documentos escaneados';

    public const TIPOS = [
        'acta_firmada' => 'Acta de conformidad firmada',
        'orden_servicio' => 'Orden de servicio firmada',
        'garantia' => 'Certificado de garantía',
        'recepcion' => 'Acta de entrega / recepción',
        'memoria_calculo' => 'Memoria de cálculo técnico',
        'planos_asbuilt' => 'Planos de instalación / as-built',
        'manual' => 'Manual de usuario y servicio',
        'manifiesto_residuos' => 'Manifiesto de disposición de residuos (gas quemado/aceite)',
        'otro' => 'Otro',
    ];

    /** Catálogo: a qué tipos de OT aplica cada documento (null = todas) y si es obligatorio. */
    public const CATALOGO = [
        'acta_firmada' => ['aplica' => null, 'obligatorio' => true],
        'orden_servicio' => ['aplica' => null, 'obligatorio' => true],
        'recepcion' => ['aplica' => ['instalacion'], 'obligatorio' => true],
        'garantia' => ['aplica' => ['instalacion'], 'obligatorio' => true],
        'memoria_calculo' => ['aplica' => ['instalacion'], 'obligatorio' => false],
        'planos_asbuilt' => ['aplica' => ['instalacion'], 'obligatorio' => false],
        'manual' => ['aplica' => ['instalacion'], 'obligatorio' => false],
        'manifiesto_residuos' => ['aplica' => null, 'obligatorio' => false],
    ];

    /** En la vista del técnico el relation manager sería de solo lectura y ocultaría "Subir". */
    public function isReadOnly(): bool
    {
        return false;
    }

    private function puedeSubir(): bool
    {
        $u = auth()->user();

        return (bool) $u?->hasAnyRole(['administrador', 'coordinador', 'tecnico', 'supervisor']);
    }

    private function puedeBorrar(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrador', 'coordinador', 'supervisor']);
    }

    /** @return array<int,string> tipos ya subidos a esta OT */
    private function tiposSubidos(): array
    {
        return \Spatie\MediaLibrary\MediaCollections\Models\Media::query()
            ->where('model_type', \App\Models\OrdenTrabajo::class)
            ->where('model_id', $this->getOwnerRecord()->getKey())
            ->where('collection_name', 'documentos_ot')
            ->get()
            ->map(fn ($m) => $m->getCustomProperty('tipo'))
            ->filter()->unique()->values()->all();
    }

    /** Catálogo filtrado por el tipo de esta OT. */
    private function catalogoAplicable(): array
    {
        $tipo = $this->getOwnerRecord()->tipo;
        $tipo = $tipo instanceof \BackedEnum ? $tipo->value : (string) $tipo;

        return array_filter(self::CATALOGO, fn ($c) => $c['aplica'] === null || in_array($tipo, $c['aplica'], true));
    }

    private function opcionesConEstado(): array
    {
        $subidos = $this->tiposSubidos();
        $catalogo = $this->catalogoAplicable();
        $op = [];

        foreach (self::TIPOS as $k => $etiqueta) {
            if ($k !== 'otro' && ! isset($catalogo[$k])) {
                continue;
            }
            $marca = $k === 'otro' ? '' : (in_array($k, $subidos, true) ? '✔ ' : ($catalogo[$k]['obligatorio'] ? '● pendiente: ' : '○ '));
            $op[$k] = $marca . $etiqueta;
        }

        return $op;
    }

    private function textoPendientes(): string
    {
        $subidos = $this->tiposSubidos();
        $falta = [];

        foreach ($this->catalogoAplicable() as $k => $c) {
            if ($c['obligatorio'] && ! in_array($k, $subidos, true)) {
                $falta[] = self::TIPOS[$k];
            }
        }

        return $falta ? 'Pendientes: ' . implode(' · ', $falta) : 'Documentación obligatoria completa ✔';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => \Spatie\MediaLibrary\MediaCollections\Models\Media::query()
                ->where('model_type', \App\Models\OrdenTrabajo::class)
                ->where('model_id', $this->getOwnerRecord()->getKey())
                ->where('collection_name', 'documentos_ot'))
            ->description(fn () => $this->textoPendientes())
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
                    ->label('Escanear / subir documento')
                    ->icon('heroicon-o-camera')
                    ->visible(fn () => $this->puedeSubir())
                    ->modalHeading('Escanear o subir documento')
                    ->schema([
                        Select::make('tipo')->label('Tipo de documento')->options(fn () => $this->opcionesConEstado())->required(),
                        TextInput::make('nombre')->label('Nombre o descripción')->required()->maxLength(150),
                        FileUpload::make('archivos')
                            ->label('Fotos de las hojas (en orden) o un PDF. Máx. 15 MB c/u')
                            ->helperText('En el celular, toca para abrir la cámara. Si subes varias fotos se unen en un solo PDF.')
                            ->multiple()
                            ->maxFiles(20)
                            ->reorderable()
                            ->disk('local')
                            ->directory('tmp-documentos-ot')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->extraInputAttributes(['capture' => 'environment'])
                            ->maxSize(15360)
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        abort_unless($this->puedeSubir(), 403);

                        $rutas = array_map(fn ($a) => Storage::disk('local')->path($a), (array) $data['archivos']);
                        $slug = Str::slug($data['nombre']) . '-' . Str::lower(Str::random(6));
                        $temporales = $rutas;

                        $pdf = collect($rutas)->first(fn ($r) => strtolower(pathinfo($r, PATHINFO_EXTENSION)) === 'pdf');

                        if ($pdf && count($rutas) === 1) {
                            $final = $pdf;
                            $ext = 'pdf';
                        } elseif ($pdf) {
                            Notification::make()->danger()->title('Sube un solo PDF, o solo fotos. No se pueden mezclar.')->send();

                            return;
                        } else {
                            $final = $this->unirImagenesEnPdf($rutas, $slug);
                            $temporales[] = $final;
                            $ext = 'pdf';
                        }

                        $this->getOwnerRecord()
                            ->addMedia($final)
                            ->preservingOriginal()
                            ->usingName($data['nombre'])
                            ->usingFileName($slug . '.' . $ext)
                            ->withCustomProperties(['tipo' => $data['tipo']])
                            ->toMediaCollection('documentos_ot');

                        foreach ($temporales as $t) {
                            @unlink($t);
                        }

                        Notification::make()->success()->title('Documento guardado')->send();
                    }),
            ])
            ->recordActions([
                Action::make('descargar')
                    ->label('Ver / descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => route('ordenes-trabajo.documento', $record->id), shouldOpenInNewTab: true),
                DeleteAction::make()->visible(fn () => $this->puedeBorrar()),
            ])
            ->emptyStateHeading('Sin documentos escaneados');
    }

    /** Une las imágenes (una por página) en un PDF con dompdf y devuelve la ruta del archivo temporal. */
    private function unirImagenesEnPdf(array $rutas, string $slug): string
    {
        $paginas = '';
        foreach ($rutas as $i => $r) {
            $mime = strtolower(pathinfo($r, PATHINFO_EXTENSION)) === 'png' ? 'image/png' : 'image/jpeg';
            $src = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($r));
            $salto = $i > 0 ? 'page-break-before:always;' : '';
            $paginas .= '<div style="' . $salto . 'text-align:center"><img src="' . $src . '" style="max-width:100%;max-height:980px"></div>';
        }

        $html = '<html><head><meta charset="utf-8"><style>@page{margin:20px}body{margin:0}</style></head><body>' . $paginas . '</body></html>';

        $salida = Storage::disk('local')->path('tmp-documentos-ot/' . $slug . '.pdf');
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        file_put_contents($salida, $dompdf->output());

        return $salida;
    }
}

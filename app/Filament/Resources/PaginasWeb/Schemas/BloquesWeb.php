<?php

namespace App\Filament\Resources\PaginasWeb\Schemas;

use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

/** Las secciones que se pueden agregar a una página. Cada una tiene su vista en cada tema (resources/views/web). */
class BloquesWeb
{
    public static function constructor(): Builder
    {
        return Builder::make('bloques')
            ->label('Secciones de la página')
            ->addActionLabel('Agregar sección')
            ->blockNumbers(false)
            ->collapsible()
            ->reorderable()
            ->cloneable()
            ->blockPickerColumns(2)
            ->columnSpanFull()
            ->blocks([
                Block::make('carrusel')->label('Carrusel de portada (varias diapositivas)')->icon('heroicon-o-film')->schema([
                    static::lista('slides', 'Diapositivas', [
                        static::imagen('imagen', 'Imagen de fondo (vacío = foto de ejemplo)'),
                        TextInput::make('titulo')->label('Título')->required()->maxLength(160),
                        Textarea::make('texto')->label('Texto')->rows(2)->maxLength(300),
                        TextInput::make('boton_texto')->label('Botón: texto')->maxLength(60),
                        static::enlace('boton_url', 'Botón: enlace'),
                    ], 'Agregar diapositiva'),
                ]),

                Block::make('hero')->label('Portada (título grande)')->icon('heroicon-o-sparkles')->columns(2)->schema([
                    TextInput::make('titulo')->label('Título')->required()->maxLength(160)->columnSpanFull(),
                    Textarea::make('texto')->label('Texto')->rows(3)->maxLength(500)->columnSpanFull(),
                    TextInput::make('boton1_texto')->label('Botón 1: texto')->maxLength(60),
                    static::enlace('boton1_url', 'Botón 1: enlace'),
                    TextInput::make('boton2_texto')->label('Botón 2: texto')->maxLength(60),
                    static::enlace('boton2_url', 'Botón 2: enlace'),
                    static::imagen('imagen', 'Imagen (vacío = la de ejemplo del tema)')->columnSpanFull(),
                ]),

                Block::make('acerca')->label('Nosotros (texto y 4 fotos)')->icon('heroicon-o-home')->columns(2)->schema([
                    static::ancla('nosotros'), static::fondo(),
                    TextInput::make('titulo')->label('Título')->required()->maxLength(200)->columnSpanFull(),
                    static::lista('destacados', 'Puntos destacados (con icono)', [
                        static::icono(),
                        TextInput::make('texto')->label('Texto')->required()->maxLength(80),
                    ], 'Agregar punto')->columnSpanFull(),
                    static::texto('parrafo', 'Texto')->columnSpanFull(),
                    Toggle::make('mostrar_contacto')->label('Mostrar teléfono y correo')->default(true)->columnSpanFull(),
                    static::imagen('imagen1', 'Foto 1 (arriba, izquierda)'),
                    static::imagen('imagen2', 'Foto 2 (arriba, derecha)'),
                    static::imagen('imagen3', 'Foto 3 (abajo, izquierda)'),
                    static::imagen('imagen4', 'Foto 4 (abajo, derecha)'),
                ]),

                Block::make('razones')->label('Razones para elegirte (iconos y foto)')->icon('heroicon-o-star')->schema([
                    static::ancla(), static::fondo(),
                    TextInput::make('titulo')->label('Título')->required()->maxLength(200),
                    Textarea::make('parrafo')->label('Texto')->rows(3)->maxLength(600),
                    static::lista('items', 'Razones', [
                        static::icono(),
                        TextInput::make('titulo')->label('Título')->required()->maxLength(120),
                        Textarea::make('texto')->label('Texto')->rows(2)->maxLength(300),
                    ], 'Agregar razón'),
                    static::imagen('imagen', 'Foto (vacío = foto de ejemplo)'),
                ]),

                Block::make('texto')->label('Texto')->icon('heroicon-o-document-text')->schema([
                    static::ancla(), static::fondo(),
                    TextInput::make('titulo')->label('Título (opcional)')->maxLength(160),
                    static::texto('contenido', 'Contenido'),
                ]),

                Block::make('imagen_texto')->label('Imagen y texto')->icon('heroicon-o-photo')->columns(2)->schema([
                    static::ancla(), static::fondo(),
                    static::imagen('imagen', 'Imagen')->columnSpanFull(),
                    Select::make('posicion')->label('Imagen a la')->options(['izquierda' => 'Izquierda', 'derecha' => 'Derecha'])->default('izquierda'),
                    TextInput::make('titulo')->label('Título')->maxLength(160),
                    static::texto('contenido', 'Contenido')->columnSpanFull(),
                    TextInput::make('boton_texto')->label('Botón: texto (opcional)')->maxLength(60),
                    static::enlace('boton_url', 'Botón: enlace'),
                ]),

                Block::make('servicios')->label('Servicios (de «Servicios del sitio»)')->icon('heroicon-o-wrench-screwdriver')->schema([
                    static::ancla('servicios'), static::fondo(),
                    TextInput::make('titulo')->label('Título')->default('Servicios')->maxLength(200),
                    Textarea::make('intro')->label('Introducción')->rows(2)->maxLength(300),
                    Toggle::make('mostrar_detalle')->label('Mostrar descripción y precio bajo cada servicio')->default(true)
                        ->helperText('Solo en el tema AirCon.'),
                ]),

                Block::make('pasos')->label('Pasos numerados')->icon('heroicon-o-list-bullet')->schema([
                    static::ancla(), static::fondo(),
                    TextInput::make('titulo')->label('Título')->maxLength(160),
                    Textarea::make('intro')->label('Introducción')->rows(2)->maxLength(300),
                    static::lista('items', 'Pasos', [
                        TextInput::make('titulo')->label('Título')->required()->maxLength(120),
                        Textarea::make('texto')->label('Texto')->rows(2)->maxLength(400),
                    ], 'Agregar paso'),
                ]),

                Block::make('caracteristicas')->label('Características en cuadrícula')->icon('heroicon-o-squares-2x2')->schema([
                    static::ancla(), static::fondo('banda'),
                    TextInput::make('titulo')->label('Título')->maxLength(160),
                    Textarea::make('intro')->label('Introducción')->rows(2)->maxLength(300),
                    static::lista('items', 'Elementos', [
                        TextInput::make('titulo')->label('Título')->required()->maxLength(120),
                        Textarea::make('texto')->label('Texto')->rows(2)->maxLength(400),
                    ], 'Agregar elemento'),
                ]),

                Block::make('galeria')->label('Galería de imágenes')->icon('heroicon-o-photo')->schema([
                    static::ancla(), static::fondo(),
                    TextInput::make('titulo')->label('Título (opcional)')->maxLength(160),
                    Textarea::make('intro')->label('Introducción (opcional)')->rows(2)->maxLength(300),
                    Select::make('columnas')->label('Columnas en pantalla grande')
                        ->options(['2' => '2', '3' => '3', '4' => '4'])->default('3'),
                    static::lista('imagenes', 'Imágenes', [
                        static::imagen('imagen', 'Imagen')->required(),
                        TextInput::make('pie')->label('Pie de foto (opcional)')->maxLength(160),
                    ], 'Agregar imagen'),
                ]),

                Block::make('faq')->label('Preguntas frecuentes')->icon('heroicon-o-question-mark-circle')->schema([
                    static::ancla(), static::fondo(),
                    TextInput::make('titulo')->label('Título')->default('Preguntas frecuentes')->maxLength(160),
                    static::lista('items', 'Preguntas', [
                        TextInput::make('pregunta')->label('Pregunta')->required()->maxLength(200),
                        Textarea::make('respuesta')->label('Respuesta')->rows(3)->required()->maxLength(1500),
                    ], 'Agregar pregunta'),
                ]),

                Block::make('cta')->label('Llamada a la acción')->icon('heroicon-o-megaphone')->columns(2)->schema([
                    TextInput::make('titulo')->label('Título')->required()->maxLength(160)->columnSpanFull(),
                    Textarea::make('texto')->label('Texto')->rows(2)->maxLength(300)->columnSpanFull(),
                    TextInput::make('boton_texto')->label('Botón: texto')->maxLength(60),
                    static::enlace('boton_url', 'Botón: enlace'),
                ]),

                Block::make('contacto')->label('Formulario de contacto y datos')->icon('heroicon-o-envelope')->schema([
                    static::ancla('contacto'), static::fondo('claro'),
                    TextInput::make('titulo')->label('Título')->default('Pide tu cotización')->maxLength(200),
                    Textarea::make('intro')->label('Introducción')->rows(2)->maxLength(300),
                    Textarea::make('mapa')->label('Mapa de Google (opcional, tema AirCon)')->rows(3)
                        ->helperText('En Google Maps: Compartir → Insertar un mapa → copia el código y pégalo aquí. Si lo dejas vacío se muestran tus datos de contacto.'),
                ]),

                Block::make('ciudades')->label('Ciudades donde atienden')->icon('heroicon-o-map-pin')->schema([
                    static::ancla(), static::fondo(),
                    TextInput::make('titulo')->label('Título')->default('Dónde atendemos')->maxLength(160),
                    Textarea::make('intro')->label('Introducción')->rows(2)->maxLength(300)
                        ->helperText('Las ciudades se editan en «Identidad, textos y contacto». Si no hay ciudades, la sección no se muestra.'),
                ]),

                Block::make('incrustado')->label('Mapa o video (código incrustado)')->icon('heroicon-o-code-bracket')->schema([
                    static::ancla(), static::fondo(),
                    TextInput::make('titulo')->label('Título (opcional)')->maxLength(160),
                    Textarea::make('codigo')->label('Código')->rows(5)
                        ->helperText('Pega el código «Insertar» de Google Maps, YouTube, etc. Solo pegues código de sitios de confianza.'),
                ]),
            ]);
    }

    protected static function enlace(string $name, string $label): TextInput
    {
        return TextInput::make($name)->label($label)->maxLength(255)
            ->regex('/^(https?:\/\/|\/|#|mailto:|tel:)/i')
            ->validationMessages(['regex' => 'Debe empezar con https://, /, #, mailto: o tel:'])
            ->placeholder('/#contacto · /nosotros · https://…');
    }

    protected static function ancla(?string $default = null): TextInput
    {
        return TextInput::make('ancla')->label('Ancla (opcional)')->alphaDash()->maxLength(40)->default($default)
            ->helperText('Nombre para enlazar a esta sección. Ej. «servicios» → /#servicios');
    }

    protected static function fondo(string $default = 'claro'): Select
    {
        return Select::make('fondo')->label('Fondo')->required()->default($default)
            ->options(['claro' => 'Normal', 'blanco' => 'Blanco con bordes', 'banda' => 'Banda de color'])
            ->helperText('En AirCon: Normal y Blanco = fondo blanco; Banda = gris claro.');
    }

    protected static function icono(): Select
    {
        $opciones = [];
        foreach (range(1, 10) as $n) {
            $k = sprintf('%02d', $n);
            $opciones[$k] = '<span style="display:inline-flex;align-items:center;gap:8px"><img src="'
                . asset("aircon/img/icon/icon-{$k}-primary.png") . '" alt="" style="width:26px;height:26px"> Icono ' . $k . '</span>';
        }

        return Select::make('icono')->label('Icono')->options($opciones)->allowHtml()->native(false)->default('01')->required();
    }

    protected static function imagen(string $name, string $label): FileUpload
    {
        return FileUpload::make($name)->label($label)->image()
            ->disk('public')->directory('paginas')->visibility('public')->maxSize(4096);
    }

    protected static function texto(string $name, string $label): RichEditor
    {
        return RichEditor::make($name)->label($label)
            ->fileAttachmentsDisk('public')->fileAttachmentsDirectory('paginas-contenido')->fileAttachmentsVisibility('public');
    }

    protected static function lista(string $name, string $label, array $campos, string $accion): Repeater
    {
        return Repeater::make($name)->label($label)->schema($campos)
            ->addActionLabel($accion)->reorderable()->collapsible()->defaultItems(0);
    }
}

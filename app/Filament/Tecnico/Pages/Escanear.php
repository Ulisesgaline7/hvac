<?php

namespace App\Filament\Tecnico\Pages;

use Filament\Pages\Page;

class Escanear extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';
    protected static ?string $navigationLabel = 'Escanear equipo';
    protected static ?string $title = 'Escanear equipo';
    protected static ?string $slug = 'escanear';
    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.tecnico.pages.escanear';
}

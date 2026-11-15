<?php
namespace App\Filament\Widgets;
use App\Models\Cotizacion;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
class ResumenComercialWidget extends StatsOverviewWidget
{
protected static ?int $sort = 1;
protected ?string $heading = "Comercial";
public static function canView(): bool
{ return auth()->user()?->hasAnyRole(["comercial","administrador","coordinador"]) ?? false; }
private function base()
{ $q = Cotizacion::query(); if (auth()->user()?->hasRole("comercial")) { $q->where("vendedor_id", auth()->id()); } return $q; }
protected function getStats(): array
{
$total = (float) $this->base()->whereYear("fecha", now()->year)->whereMonth("fecha", now()->month)->sum("total");
return [
Stat::make("Cotizaciones del mes", $this->base()->whereYear("fecha", now()->year)->whereMonth("fecha", now()->month)->count())->description("Total: L ".number_format($total, 2)),
Stat::make("Enviadas sin respuesta", $this->base()->where("estado", "enviada")->count())->color("warning"),
Stat::make("Aprobadas del mes", $this->base()->where("estado", "aprobada")->whereYear("aprobada_en", now()->year)->whereMonth("aprobada_en", now()->month)->count())->color("success"),
Stat::make("Vencen en 7 dias", $this->base()->where("estado", "enviada")->whereBetween("valida_hasta", [today(), today()->addDays(7)])->count())->color("danger"),
];
}
}

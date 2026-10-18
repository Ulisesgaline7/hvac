<?php

namespace Database\Seeders;

use App\Models\PlantillaFormulario;
use App\Models\TipoEquipo;
use Illuminate\Database\Seeder;

class PlantillasSeeder extends Seeder
{
    private function c(string $clave, string $etiqueta, string $tipo = 'texto', array $extra = []): array
    {
        return array_merge(['clave' => $clave, 'etiqueta' => $etiqueta, 'tipo' => $tipo, 'obligatorio' => false], $extra);
    }

    private function comunes(): array
    {
        return [
            $this->c('motivo_visita', 'Motivo de la visita', 'texto', ['obligatorio' => true]),
            $this->c('refrigerante_tipo', 'Refrigerante intervenido (tipo)'),
            $this->c('refrigerante_cantidad', 'Refrigerante agregado o recuperado', 'numero', ['unidad' => 'kg']),
            $this->c('pruebas_finales', 'Pruebas finales', 'nota'),
            $this->c('recomendaciones', 'Recomendaciones', 'nota'),
            $this->c('foto_antes', 'Foto antes', 'foto'),
            $this->c('foto_despues', 'Foto después', 'foto'),
            $this->c('firma_tecnico', 'Firma del técnico', 'firma', ['obligatorio' => true]),
            $this->c('firma_cliente', 'Firma del cliente', 'firma', ['obligatorio' => true]),
        ];
    }

    public function run(): void
    {
        $plantillas = [
            'aire_acondicionado' => ['Aire acondicionado', [
                $this->c('filtros_limpios', 'Filtros limpios', 'casilla'),
                $this->c('serpentines_limpios', 'Serpentines evaporador y condensador limpios', 'casilla'),
                $this->c('drenajes_ok', 'Drenajes despejados', 'casilla'),
                $this->c('ventiladores_ok', 'Ventiladores en buen estado', 'casilla'),
                $this->c('conexiones_ok', 'Conexiones eléctricas revisadas', 'casilla'),
                $this->c('temp_retorno', 'Temperatura de retorno', 'numero', ['unidad' => '°C', 'obligatorio' => true]),
                $this->c('temp_suministro', 'Temperatura de suministro', 'numero', ['unidad' => '°C', 'obligatorio' => true]),
                $this->c('corriente_compresor', 'Corriente del compresor', 'numero', ['unidad' => 'A']),
                $this->c('voltaje_compresor', 'Voltaje del compresor', 'numero', ['unidad' => 'V']),
                $this->c('presion_succion', 'Presión de succión', 'numero', ['unidad' => 'psi']),
                $this->c('presion_descarga', 'Presión de descarga', 'numero', ['unidad' => 'psi']),
                $this->c('sobrecalentamiento', 'Sobrecalentamiento', 'numero', ['unidad' => 'K']),
                $this->c('subenfriamiento', 'Subenfriamiento', 'numero', ['unidad' => 'K']),
                $this->c('condicion_general', 'Condición general', 'seleccion', ['opciones' => ['Buena', 'Regular', 'Mala']]),
            ]],
            'cuartos_frios' => ['Cuartos fríos y congeladores', [
                $this->c('temp_objetivo', 'Temperatura objetivo', 'numero', ['unidad' => '°C', 'obligatorio' => true]),
                $this->c('temp_real', 'Temperatura real', 'numero', ['unidad' => '°C', 'obligatorio' => true, 'min' => -30, 'max' => 8]),
                $this->c('presion_succion', 'Presión de succión', 'numero', ['unidad' => 'psi']),
                $this->c('presion_descarga', 'Presión de descarga', 'numero', ['unidad' => 'psi']),
                $this->c('ciclo_deshielo', 'Ciclo de deshielo correcto', 'casilla'),
                $this->c('empaques_ok', 'Empaques de puertas en buen estado', 'casilla'),
                $this->c('drenajes_ok', 'Drenajes despejados', 'casilla'),
                $this->c('resistencias_ok', 'Resistencias funcionando', 'casilla'),
                $this->c('alarmas_controlador', 'Alarmas y controlador', 'nota'),
                $this->c('corriente_compresor', 'Corriente del compresor', 'numero', ['unidad' => 'A']),
                $this->c('voltaje_compresor', 'Voltaje del compresor', 'numero', ['unidad' => 'V']),
            ]],
            'refrigeracion_comercial' => ['Refrigeración comercial', [
                $this->c('temp_operacion', 'Temperatura de operación', 'numero', ['unidad' => '°C', 'obligatorio' => true]),
                $this->c('condensador_limpio', 'Condensador limpio', 'casilla'),
                $this->c('empaques_ok', 'Empaques en buen estado', 'casilla'),
                $this->c('compresor_ok', 'Compresor revisado', 'casilla'),
                $this->c('ventiladores_ok', 'Ventiladores revisados', 'casilla'),
                $this->c('drenaje_ok', 'Drenaje despejado', 'casilla'),
                $this->c('produccion_hielo', 'Producción de hielo (si aplica)', 'numero', ['unidad' => 'kg/día']),
            ]],
        ];

        foreach ($plantillas as $familia => [$nombre, $campos]) {
            $this->crear($familia, "Mantenimiento: $nombre", array_merge($campos, $this->comunes()));
        }

        $this->crear('instalacion', 'Instalación y puesta en marcha', [
            $this->c('marca', 'Marca', 'texto', ['obligatorio' => true]),
            $this->c('modelo', 'Modelo', 'texto', ['obligatorio' => true]),
            $this->c('serie', 'Serie', 'texto', ['obligatorio' => true]),
            $this->c('capacidad', 'Capacidad (BTU o toneladas)', 'texto'),
            $this->c('refrigerante_tipo', 'Refrigerante cargado (tipo)', 'texto', ['obligatorio' => true]),
            $this->c('refrigerante_cantidad', 'Refrigerante cargado en la instalación', 'numero', ['unidad' => 'kg', 'obligatorio' => true]),
            $this->c('ubicacion_exacta', 'Ubicación exacta', 'texto', ['obligatorio' => true]),
            $this->c('verif_electrica', 'Verificación eléctrica', 'casilla'),
            $this->c('verif_tuberias', 'Verificación de tuberías', 'casilla'),
            $this->c('verif_drenaje', 'Verificación de drenaje', 'casilla'),
            $this->c('prueba_hermeticidad', 'Vacío o prueba de hermeticidad', 'seleccion', ['opciones' => ['Vacío', 'Hermeticidad', 'No aplica']]),
            $this->c('presion_succion', 'Presión de succión al arranque', 'numero', ['unidad' => 'psi']),
            $this->c('presion_descarga', 'Presión de descarga al arranque', 'numero', ['unidad' => 'psi']),
            $this->c('temp_arranque', 'Temperatura al arranque', 'numero', ['unidad' => '°C']),
            $this->c('corriente_arranque', 'Corriente al arranque', 'numero', ['unidad' => 'A']),
            $this->c('pruebas_finales', 'Pruebas finales', 'nota', ['obligatorio' => true]),
            $this->c('capacitacion', 'Capacitación al usuario realizada', 'casilla'),
            $this->c('foto_instalacion', 'Fotos de la instalación', 'foto'),
            $this->c('firma_tecnico', 'Firma del técnico', 'firma', ['obligatorio' => true]),
            $this->c('firma_cliente', 'Firma del cliente (acta de entrega)', 'firma', ['obligatorio' => true]),
        ]);

        // Versión 2: formato físico (antes/después, verificación OK/Obs/N/A, firmas).
        foreach (\App\Support\PlantillasV2::todas() as $familia => $campos) {
            $p = PlantillaFormulario::where('familia', $familia)->first();
            if ($p && $p->versiones()->count() < 2) {
                $p->publicar($campos);
            }
        }

        // Vincula cada tipo de equipo con la plantilla de su familia, si aún no tiene una.
        foreach (PlantillaFormulario::all() as $p) {
            TipoEquipo::where('familia', $p->familia)->whereNull('plantilla_id')->update(['plantilla_id' => $p->id]);
        }
    }

    private function crear(string $familia, string $nombre, array $campos): void
    {
        $p = PlantillaFormulario::firstOrCreate(['nombre' => $nombre], ['familia' => $familia]);

        if ($p->versiones()->doesntExist()) {
            $p->publicar($campos);
        }
    }
}

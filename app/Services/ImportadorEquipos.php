<?php

namespace App\Services;

use App\Enums\EstadoEquipo;
use App\Models\Area;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\Marca;
use App\Models\Refrigerante;
use App\Models\Sucursal;
use App\Models\TipoEquipo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportadorEquipos
{
    public const COLUMNAS = [
        'cliente', 'sucursal', 'area', 'tipo', 'marca', 'modelo', 'serie',
        'capacidad_valor', 'capacidad_unidad', 'refrigerante', 'voltaje', 'corriente',
        'fecha_instalacion', 'garantia_hasta', 'estado',
    ];

    private const OBLIGATORIAS = ['cliente', 'sucursal', 'area', 'tipo'];
    private const MAX_FILAS = 2000;

    /** @return array{creados:int, errores:array<int,array{fila:int,mensaje:string}>} */
    public function importar(string $ruta): array
    {
        $filas = $this->leer($ruta);

        if (empty($filas)) {
            return $this->fallo(1, 'El archivo está vacío.');
        }

        $encabezado = array_map(fn ($h) => Str::slug((string) $h, '_'), array_shift($filas));
        $faltan = array_diff(self::OBLIGATORIAS, $encabezado);
        if ($faltan) {
            return $this->fallo(1, 'Faltan columnas obligatorias: ' . implode(', ', $faltan));
        }

        if (count($filas) > self::MAX_FILAS) {
            return $this->fallo(1, 'El archivo supera el máximo de ' . self::MAX_FILAS . ' filas.');
        }

        $mapas = $this->cargarCatalogos();
        $errores = [];
        $validos = [];

        foreach ($filas as $i => $fila) {
            $numero = $i + 2;
            $datos = [];
            foreach ($encabezado as $pos => $col) {
                $datos[$col] = isset($fila[$pos]) ? trim((string) $fila[$pos]) : '';
            }
            if (implode('', $datos) === '') {
                continue;
            }

            [$registro, $erroresFila] = $this->validarFila($datos, $fila, $encabezado, $mapas);
            foreach ($erroresFila as $mensaje) {
                $errores[] = ['fila' => $numero, 'mensaje' => $mensaje];
            }
            if (! $erroresFila) {
                $validos[] = $registro;
            }
        }

        if ($errores) {
            return ['creados' => 0, 'errores' => $errores];
        }

        DB::transaction(function () use ($validos) {
            foreach ($validos as $registro) {
                Equipo::create($registro);
            }
        });

        return ['creados' => count($validos), 'errores' => []];
    }

    private function validarFila(array $d, array $cruda, array $encabezado, array $m): array
    {
        $e = [];
        $clave = fn (string $v) => mb_strtolower($v);

        $cliente = $m['clientes'][$clave($d['cliente'])] ?? null;
        $cliente || $e[] = "Cliente '{$d['cliente']}' no existe.";

        $sucursal = $cliente ? ($m['sucursales'][$cliente . '|' . $clave($d['sucursal'])] ?? null) : null;
        ($sucursal || ! $cliente) || $e[] = "Sucursal '{$d['sucursal']}' no existe en ese cliente.";

        $area = $sucursal ? ($m['areas'][$sucursal . '|' . $clave($d['area'])] ?? null) : null;
        ($area || ! $sucursal) || $e[] = "Área '{$d['area']}' no existe en esa sucursal.";

        $tipo = $m['tipos'][$clave($d['tipo'])] ?? null;
        $tipo || $e[] = "Tipo de equipo '{$d['tipo']}' no existe en el catálogo.";

        $marca = null;
        if (($d['marca'] ?? '') !== '') {
            $marca = $m['marcas'][$clave($d['marca'])] ?? null;
            $marca || $e[] = "Marca '{$d['marca']}' no existe en el catálogo.";
        }

        $refrigerante = null;
        if (($d['refrigerante'] ?? '') !== '') {
            $refrigerante = $m['refrigerantes'][$clave($d['refrigerante'])] ?? null;
            $refrigerante || $e[] = "Refrigerante '{$d['refrigerante']}' no existe en el catálogo.";
        }

        $capacidad = null;
        if (($d['capacidad_valor'] ?? '') !== '') {
            $num = str_replace(',', '.', $d['capacidad_valor']);
            is_numeric($num) ? $capacidad = (float) $num : $e[] = "Capacidad '{$d['capacidad_valor']}' no es un número.";
        }

        $estado = EstadoEquipo::Operativo;
        if (($d['estado'] ?? '') !== '') {
            $estado = EstadoEquipo::tryFrom(Str::slug($d['estado'], '_'));
            $estado || $e[] = "Estado '{$d['estado']}' no válido (use: operativo, con_observaciones, fuera_de_servicio, dado_de_baja).";
        }

        $fechas = [];
        foreach (['fecha_instalacion', 'garantia_hasta'] as $campo) {
            $pos = array_search($campo, $encabezado, true);
            $valor = $pos === false ? '' : ($cruda[$pos] ?? '');
            $fecha = $this->fecha($valor);
            if ($fecha === false) {
                $e[] = "Fecha '$valor' en $campo no válida (use AAAA-MM-DD o DD/MM/AAAA).";
            } else {
                $fechas[$campo] = $fecha;
            }
        }

        if ($e) {
            return [null, $e];
        }

        return [[
            'area_id' => $area,
            'tipo_equipo_id' => $tipo,
            'marca_id' => $marca,
            'modelo' => $d['modelo'] ?? null ?: null,
            'serie' => $d['serie'] ?? null ?: null,
            'capacidad_valor' => $capacidad,
            'capacidad_unidad' => ($d['capacidad_unidad'] ?? '') !== '' ? strtoupper($d['capacidad_unidad']) : null,
            'refrigerante_id' => $refrigerante,
            'voltaje' => $d['voltaje'] ?? null ?: null,
            'corriente' => $d['corriente'] ?? null ?: null,
            'fecha_instalacion' => $fechas['fecha_instalacion'] ?? null,
            'garantia_hasta' => $fechas['garantia_hasta'] ?? null,
            'estado' => $estado,
        ], []];
    }

    /** @return Carbon|null|false  null = vacío, false = inválida */
    private function fecha(mixed $valor): Carbon|null|false
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }
        if (is_numeric($valor) && (float) $valor > 20000) {
            return Carbon::instance(Date::excelToDateTimeObject((float) $valor))->startOfDay();
        }
        $texto = trim((string) $valor);
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $formato) {
            $f = \DateTimeImmutable::createFromFormat('!' . $formato, $texto);
            if ($f !== false && $f->format($formato) === $texto) {
                return Carbon::instance($f);
            }
        }
        return false;
    }

    private function cargarCatalogos(): array
    {
        $minus = fn ($coleccion, $campo) => $coleccion->mapWithKeys(fn ($f) => [mb_strtolower($f->$campo) => $f->id])->all();

        $sucursales = [];
        foreach (Sucursal::select('id', 'cliente_id', 'nombre')->get() as $s) {
            $sucursales[$s->cliente_id . '|' . mb_strtolower($s->nombre)] = $s->id;
        }
        $areas = [];
        foreach (Area::select('id', 'sucursal_id', 'nombre')->get() as $a) {
            $areas[$a->sucursal_id . '|' . mb_strtolower($a->nombre)] = $a->id;
        }

        return [
            'clientes' => $minus(Cliente::select('id', 'razon_social')->get(), 'razon_social'),
            'sucursales' => $sucursales,
            'areas' => $areas,
            'tipos' => $minus(TipoEquipo::select('id', 'nombre')->get(), 'nombre'),
            'marcas' => $minus(Marca::select('id', 'nombre')->get(), 'nombre'),
            'refrigerantes' => $minus(Refrigerante::select('id', 'nombre')->get(), 'nombre'),
        ];
    }

    private function leer(string $ruta): array
    {
        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));

        if (in_array($extension, ['csv', 'txt'], true)) {
            $lector = new Csv();
            $primera = (string) fgets(fopen($ruta, 'r'));
            $lector->setDelimiter(substr_count($primera, ';') > substr_count($primera, ',') ? ';' : ',');
            $lector->setInputEncoding('UTF-8');
        } else {
            $lector = IOFactory::createReaderForFile($ruta);
        }

        return $lector->load($ruta)->getActiveSheet()->toArray(null, true, false, false);
    }

    private function fallo(int $fila, string $mensaje): array
    {
        return ['creados' => 0, 'errores' => [['fila' => $fila, 'mensaje' => $mensaje]]];
    }
}

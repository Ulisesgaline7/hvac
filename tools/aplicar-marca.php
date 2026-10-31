<?php

/**
 * Aplica nombre, logos y color principal de la marca a los paneles de Filament.
 * Uso (desde la raíz del proyecto):  php tools/aplicar-marca.php
 * Es seguro repetirlo. Para deshacer:  git checkout -- app/Providers/Filament
 * Cuando termines puedes borrar este archivo.
 */

$raiz = dirname(__DIR__);
$archivos = glob($raiz . '/app/Providers/Filament/*PanelProvider.php');

if (! $archivos) {
    exit("No encontré paneles en app/Providers/Filament\n");
}

$marcaCall = '\App\Support\Marca';

foreach ($archivos as $archivo) {
    $nombre = basename($archivo);
    $original = file_get_contents($archivo);

    if (str_contains($original, 'Marca::')) {
        echo "= $nombre: ya tenía la marca\n";
        continue;
    }

    $pos = strpos($original, '->colors(');
    if ($pos === false) {
        echo "! $nombre: no tiene ->colors(; revísalo a mano\n";
        continue;
    }

    // Cierre del paréntesis de ->colors( ... )
    $i = $pos + strlen('->colors(');
    $prof = 1;
    $n = strlen($original);
    for (; $i < $n && $prof > 0; $i++) {
        if ($original[$i] === '(') {
            $prof++;
        } elseif ($original[$i] === ')') {
            $prof--;
        }
    }
    if ($prof !== 0) {
        echo "! $nombre: no pude leer ->colors(...); revísalo a mano\n";
        continue;
    }

    $llamada = substr($original, $pos, $i - $pos);

    // Sangría de esa línea
    $inicioLinea = strrpos(substr($original, 0, $pos), "\n") + 1;
    preg_match('/^[ \t]*/', substr($original, $inicioLinea), $m);
    $sangria = $m[0];

    // Cambia solo el color «primary»; los demás colores del panel se conservan.
    $expr = '(?:[^,\]\[()]|\((?:[^()]|\([^()]*\))*\))+';
    $nueva = preg_replace('/([\'"]primary[\'"]\s*=>\s*)' . $expr . '/', '$1' . addcslashes($marcaCall, '\\$') . '::primario()', $llamada, 1, $cambios);

    if ($cambios === 0) {
        if (! str_contains($llamada, '[')) {
            echo "! $nombre: ->colors(...) no es un arreglo; revísalo a mano\n";
            continue;
        }
        $nueva = preg_replace('/\[/', "['primary' => $marcaCall::primario(), ", $llamada, 1);
    }

    $lineas = [];
    foreach ([
        'brandName' => "$marcaCall::nombre()",
        'brandLogo' => "$marcaCall::logoClaro()",
        'darkModeBrandLogo' => "$marcaCall::logoOscuro()",
        'brandLogoHeight' => "$marcaCall::alturaLogo()",
        'favicon' => "$marcaCall::favicon()",
    ] as $metodo => $valor) {
        if (! str_contains($original, "->$metodo(")) {
            $lineas[] = "\n{$sangria}->{$metodo}({$valor})";
        }
    }

    $nuevo = substr($original, 0, $pos) . $nueva . implode('', $lineas) . substr($original, $i);
    file_put_contents($archivo, $nuevo);

    exec('php -l ' . escapeshellarg($archivo) . ' 2>&1', $salida, $codigo);
    if ($codigo !== 0) {
        file_put_contents($archivo, $original);
        echo "! $nombre: el resultado tenía un error de sintaxis; lo dejé como estaba\n";
        continue;
    }

    echo "+ $nombre: marca aplicada\n";
}

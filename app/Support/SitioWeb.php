<?php

namespace App\Support;

use App\Models\AjusteWeb;
use App\Models\MenuItemWeb;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Throwable;

final class SitioWeb
{
    public const TEMAS = ['clasico' => 'Clásico (diseño anterior)', 'aircon' => 'AirCon'];

    public static function ajustes(): array
    {
        return AjusteWeb::valores();
    }

    public static function nombre(): string
    {
        return self::ajustes()['sitio_nombre'] ?? 'Control Total';
    }

    // ---------------------------------------------------------------- tema

    /** Tema publicado (el que ve todo el mundo). */
    public static function temaPublicado(): string
    {
        $t = self::ajustes()['tema'] ?? 'clasico';

        return array_key_exists($t, self::TEMAS) ? $t : 'clasico';
    }

    /**
     * Tema a usar en esta visita. Quien entra al panel puede probar otro con «?tema=aircon»
     * (queda en su sesión) y salir con «?tema=salir». El resto siempre ve el publicado.
     */
    public static function tema(): string
    {
        $attrs = request()->attributes;
        if ($attrs->has('web_tema')) {
            return $attrs->get('web_tema');
        }

        $tema = self::temaPublicado();

        if (self::puedePrevisualizar()) {
            $pedido = request()->query('tema');
            if ($pedido === 'salir') {
                session()->forget('tema_preview');
            } elseif (is_string($pedido) && array_key_exists($pedido, self::TEMAS)) {
                session(['tema_preview' => $pedido]);
            }

            $guardado = session('tema_preview');
            if (is_string($guardado) && array_key_exists($guardado, self::TEMAS)) {
                $tema = $guardado;
            }
        }

        $attrs->set('web_tema', $tema);

        return $tema;
    }

    /** Si alguien está probando un tema distinto al publicado, devuelve su nombre; si no, null. */
    public static function avisoVistaPrevia(): ?array
    {
        $tema = self::tema();

        return $tema !== self::temaPublicado() ? ['nombre' => self::TEMAS[$tema]] : null;
    }

    /** Nombre de la vista según el tema: vista('layout'), vista('bloques.hero')… */
    public static function vista(string $nombre): string
    {
        return self::tema() === 'aircon' ? 'web.temas.aircon.' . $nombre : 'web.' . $nombre;
    }

    public static function puedePrevisualizar(): bool
    {
        $attrs = request()->attributes;
        if ($attrs->has('web_puede_previsualizar')) {
            return $attrs->get('web_puede_previsualizar');
        }

        $ok = false;
        try {
            $u = auth()->user();
            if ($u && method_exists($u, 'canAccessPanel')) {
                $ok = (bool) $u->canAccessPanel(Filament::getPanel('admin'));
            }
        } catch (Throwable) {
            $ok = false;
        }

        $attrs->set('web_puede_previsualizar', $ok);

        return $ok;
    }

    /** Variables de color del tema (solo las que se cambiaron en «Identidad»). Vacío = colores originales. */
    public static function coloresCss(): string
    {
        $aj = self::ajustes();
        $mapa = ['color_primario' => '--primary', 'color_secundario' => '--secondary', 'color_claro' => '--light', 'color_oscuro' => '--dark'];
        $css = '';
        foreach ($mapa as $clave => $variable) {
            $v = trim((string) ($aj[$clave] ?? ''));
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $v)) {
                $css .= $variable . ':' . $v . ';';
            }
        }

        return $css;
    }

    // ------------------------------------------------------------ imágenes

    /** URL de una imagen subida desde el panel (disco público) o, si no hay, de la que viene en /public. */
    public static function imagen(?string $ruta, string $defecto): string
    {
        return filled($ruta) ? asset('storage/' . ltrim($ruta, '/')) : asset($defecto);
    }

    /** Igual que imagen(), pero para valores guardados dentro de las secciones (puede ser texto o lista). */
    public static function urlImagen(mixed $valor, ?string $defecto = null): ?string
    {
        if (is_array($valor)) {
            $valor = reset($valor) ?: null;
        }

        if (filled($valor)) {
            return asset('storage/' . ltrim((string) $valor, '/'));
        }

        return $defecto ? asset($defecto) : null;
    }

    public static function tipoImagen(?string $ruta): string
    {
        return match (strtolower(pathinfo((string) $ruta, PATHINFO_EXTENSION))) {
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    /** Icono de la plantilla AirCon (01 a 10) en la variante «primary» o «light». */
    public static function iconoAircon(?string $numero, string $variante = 'primary'): string
    {
        $n = (int) $numero;
        $n = $n >= 1 && $n <= 10 ? $n : 1;

        return asset(sprintf('aircon/img/icon/icon-%02d-%s.png', $n, $variante === 'light' ? 'light' : 'primary'));
    }

    // -------------------------------------------------------------- enlaces

    /** Solo deja pasar enlaces conocidos (nada de «javascript:»). */
    public static function enlace(?string $u): ?string
    {
        $u = trim((string) $u);

        return preg_match('~^(https?://|/|#|mailto:|tel:)~i', $u) ? $u : null;
    }

    /** Acepta la dirección o el código «Insertar un mapa» completo y devuelve solo una dirección de mapa permitida. */
    public static function mapaSrc(?string $codigo): ?string
    {
        $codigo = trim((string) $codigo);
        if ($codigo === '') {
            return null;
        }

        if (preg_match('~src\s*=\s*["\']([^"\']+)["\']~i', $codigo, $m)) {
            $codigo = html_entity_decode($m[1]);
        }

        $permitido = '~^https://(www\.google\.[a-z.]+/maps/embed|maps\.google\.[a-z.]+/|www\.openstreetmap\.org/export/embed\.html)~i';

        return preg_match($permitido, $codigo) ? $codigo : null;
    }

    /** ¿El enlace del menú apunta a la página actual? */
    public static function esActivo(?string $href): bool
    {
        if (! $href || str_contains($href, '#')) {
            return false;
        }

        $p = parse_url($href);
        if (! empty($p['host']) && $p['host'] !== request()->getHost()) {
            return false;
        }

        $destino = '/' . trim($p['path'] ?? '/', '/');
        $actual = '/' . trim(request()->path(), '/');

        return $destino === $actual;
    }

    /** Redes sociales con dirección válida: [clave => [nombre, icono, url]]. */
    public static function redes(): array
    {
        $aj = self::ajustes();
        $def = [
            'facebook' => ['Facebook', 'fab fa-facebook-f'],
            'twitter' => ['X / Twitter', 'fab fa-twitter'],
            'linkedin' => ['LinkedIn', 'fab fa-linkedin-in'],
            'instagram' => ['Instagram', 'fab fa-instagram'],
            'youtube' => ['YouTube', 'fab fa-youtube'],
            'tiktok' => ['TikTok', 'bi bi-tiktok'],
        ];

        $r = [];
        foreach ($def as $clave => [$nombre, $icono]) {
            $url = trim((string) ($aj[$clave] ?? ''));
            if (preg_match('~^https?://~i', $url)) {
                $r[$clave] = ['nombre' => $nombre, 'icono' => $icono, 'url' => $url];
            }
        }

        return $r;
    }

    // ------------------------------------------------------------ secciones

    /** Atributos de la etiqueta <section> en el tema clásico: clase, ancla y fondo. */
    public static function seccion(array $d): HtmlString
    {
        $fondo = $d['fondo'] ?? 'claro';
        $attrs = 'class="sec' . ($fondo === 'banda' ? ' band' : '') . '"';

        $ancla = self::ancla($d);
        if ($ancla !== '') {
            $attrs .= ' id="' . e($ancla) . '"';
        }
        if ($fondo === 'blanco') {
            $attrs .= ' style="background:#fff;border-block:1px solid var(--line)"';
        }

        return new HtmlString($attrs);
    }

    /** Atributos de una sección en el tema AirCon (Bootstrap). */
    public static function seccionAircon(array $d, string $extra = 'py-5'): HtmlString
    {
        $fondo = $d['fondo'] ?? 'claro';
        $clases = trim('container-xxl ' . $extra . ($fondo === 'banda' ? ' bg-light' : ($fondo === 'blanco' ? ' bg-white' : '')));
        $attrs = 'class="' . e($clases) . '"';

        $ancla = self::ancla($d);
        if ($ancla !== '') {
            $attrs .= ' id="' . e($ancla) . '"';
        }

        return new HtmlString($attrs);
    }

    private static function ancla(array $d): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($d['ancla'] ?? ''));
    }

    // ---------------------------------------------------------------- menús

    /**
     * Elementos de primer nivel de un menú, cada uno con su relación «hijosVisibles».
     * Si el menú nunca se configuró (o la tabla aún no existe) devuelve los enlaces originales del sitio.
     */
    public static function menu(string $ubicacion): Collection
    {
        try {
            if (! MenuItemWeb::query()->where('ubicacion', $ubicacion)->exists()) {
                return self::menuPorDefecto($ubicacion);
            }

            $items = MenuItemWeb::query()->with('pagina')
                ->where('ubicacion', $ubicacion)->where('visible', true)
                ->orderBy('orden')->orderBy('id')->get()
                ->filter(fn (MenuItemWeb $i) => $i->href() !== null)->values();
        } catch (Throwable) {
            return self::menuPorDefecto($ubicacion);
        }

        $hijos = $items->whereNotNull('parent_id')->groupBy('parent_id');

        return $items->whereNull('parent_id')->values()->each(
            fn (MenuItemWeb $i) => $i->setRelation('hijosVisibles', $hijos->get($i->id, collect())->values())
        );
    }

    private static function menuPorDefecto(string $ubicacion): Collection
    {
        if (! in_array($ubicacion, ['principal', 'pie'], true)) {
            return collect();
        }

        $enlaces = [
            ['Servicios', '/#servicios'], ['Cómo trabajamos', '/#proceso'],
            ['Por qué Control Total', '/#control'], ['Contacto', '/#contacto'], ['Ingresar', '/ingresar'],
        ];

        return collect($enlaces)->map(function ($e) use ($ubicacion) {
            $i = new MenuItemWeb([
                'ubicacion' => $ubicacion, 'etiqueta' => $e[0], 'tipo' => 'url', 'url' => $e[1],
                'boton' => $ubicacion === 'principal' && $e[0] === 'Ingresar',
            ]);
            $i->setRelation('hijosVisibles', collect());

            return $i;
        });
    }
}

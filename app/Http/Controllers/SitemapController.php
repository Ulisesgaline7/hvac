<?php

namespace App\Http\Controllers;

use App\Models\WebPagina;

class SitemapController
{
    public function __invoke()
    {
        $urls = [['loc' => url('/'), 'mod' => null]];
        foreach (WebPagina::where('publicada', true)->where('es_inicio', false)->orderBy('orden')->get() as $p) {
            $urls[] = ['loc' => url('/' . $p->slug), 'mod' => optional($p->updated_at)->toAtomString()];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $xml .= '<url><loc>' . e($u['loc']) . '</loc>' . ($u['mod'] ? '<lastmod>' . $u['mod'] . '</lastmod>' : '') . '</url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}

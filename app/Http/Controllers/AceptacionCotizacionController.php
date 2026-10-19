<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Providers\RlsServiceProvider;
use App\Services\CotizacionFlujo;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AceptacionCotizacionController extends Controller
{
    /** Id de la cotización enviada y vigente para ese token, o null. */
    private function idPorToken(string $token): ?int
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return DB::selectOne('select cotizacion_id_por_token(?) as id', [$token])->id;
        }

        return Cotizacion::where('token_aceptacion', $token)
            ->where('estado', 'enviada')
            ->where(fn ($q) => $q->whereNull('valida_hasta')->orWhereDate('valida_hasta', '>=', now()))
            ->value('id');
    }

    public function mostrar(string $token)
    {
        $id = $this->idPorToken($token);

        $cotizacion = $id
            ? RlsServiceProvider::comoInterno(fn () => Cotizacion::with(['cliente', 'sucursal', 'lineas'])->find($id))
            : null;

        return view('cotizaciones.aceptacion', ['cotizacion' => $cotizacion, 'token' => $token, 'resultado' => session('resultado')]);
    }

    public function aceptar(Request $request, string $token, CotizacionFlujo $flujo)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'cargo' => ['required', 'string', 'max:150'],
            'acepto' => ['accepted'],
            'firma' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:400000'],
        ]);

        $id = $this->idPorToken($token);
        abort_unless($id, 404);

        try {
            RlsServiceProvider::comoInterno(function () use ($id, $datos, $request, $flujo) {
                $c = Cotizacion::with('lineas')->findOrFail($id);

                $hash = hash('sha256', json_encode([
                    $c->numero,
                    $c->total,
                    $c->lineas->map(fn ($l) => [$l->descripcion, $l->cantidad, $l->precio_unitario])->all(),
                ]));

                $flujo->aprobar($c, $datos['nombre']);

                $c->refresh()->update([
                    'aprobada_por_cargo' => $datos['cargo'],
                    'aceptada_ip' => $request->ip(),
                    'aceptada_user_agent' => substr((string) $request->userAgent(), 0, 500),
                    'hash_documento' => $hash,
                    'firma_cliente' => $datos['firma'],
                ]);
            });
        } catch (DomainException $e) {
            return back()->withErrors(['general' => $e->getMessage() . ' Comuníquese con su asesor.'])->withInput();
        }

        return redirect()->route('cotizaciones.aceptacion', $token)
            ->with('resultado', 'Gracias. La cotización fue aceptada y su asesor dará seguimiento.');
    }

    public function rechazar(Request $request, string $token, CotizacionFlujo $flujo)
    {
        $datos = $request->validate(['motivo' => ['nullable', 'string', 'max:500']]);

        $id = $this->idPorToken($token);
        abort_unless($id, 404);

        RlsServiceProvider::comoInterno(fn () => $flujo->rechazar(Cotizacion::findOrFail($id), $datos['motivo'] ?? null));

        return redirect()->route('cotizaciones.aceptacion', $token)
            ->with('resultado', 'Registramos que la cotización no fue aceptada. Gracias por su respuesta.');
    }
}

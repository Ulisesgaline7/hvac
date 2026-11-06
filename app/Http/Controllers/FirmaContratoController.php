<?php

namespace App\Http\Controllers;

use App\Enums\EstadoContrato;
use App\Models\Contrato;
use App\Providers\RlsServiceProvider;
use App\Services\ContratoPdf;
use App\Services\FirmaPdf;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FirmaContratoController extends Controller
{
    private function idPorToken(string $token): ?int
    {
        return RlsServiceProvider::comoInterno(
            fn () => Contrato::where('token_firma', $token)
                ->where('estado', '!=', EstadoContrato::Cancelado->value)
                ->value('id')
        );
    }

    public function mostrar(string $token)
    {
        $id = $this->idPorToken($token);

        $contrato = $id
            ? RlsServiceProvider::comoInterno(fn () => Contrato::with(['cliente', 'equipos.tipo'])->find($id))
            : null;

        return view('contratos.firma', ['contrato' => $contrato, 'token' => $token, 'resultado' => session('resultado')]);
    }

    public function firmar(Request $request, string $token, ContratoPdf $pdf, FirmaPdf $sello)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'cargo' => ['required', 'string', 'max:150'],
            'acepto' => ['accepted'],
            'firma' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:400000'],
        ]);

        $id = $this->idPorToken($token);
        abort_unless($id, 404);

        $png = base64_decode(substr($datos['firma'], strlen('data:image/png;base64,')), true);
        if ($png === false || ! str_starts_with($png, "\x89PNG")) {
            return back()->withErrors(['general' => 'La firma no es válida.'])->withInput();
        }

        $disco = Storage::disk('local');
        $rutaPdf = "contratos/{$id}-firmado.pdf";
        $rutaFirma = "contratos/firmas/{$id}.png";
        $escribio = false;

        try {
            RlsServiceProvider::comoInterno(function () use ($id, $datos, $request, $png, $pdf, $sello, $disco, $rutaPdf, $rutaFirma, &$escribio) {
                DB::transaction(function () use ($id, $datos, $request, $png, $pdf, $sello, $disco, $rutaPdf, $rutaFirma, &$escribio) {
                    $c = Contrato::lockForUpdate()->findOrFail($id);

                    if ($c->firmado_at) {
                        throw new DomainException('Este contrato ya fue firmado.');
                    }

                    $escribio = true;
                    $disco->put($rutaFirma, $png);

                    $firmante = [
                        'nombre' => $datos['nombre'],
                        'cargo' => $datos['cargo'],
                        'fecha' => now()->format('d/m/Y H:i'),
                        'ip' => $request->ip(),
                    ];
                    $disco->put($rutaPdf, $pdf->render($c, $png, $firmante));
                    $hash = $sello->firmar($disco->path($rutaPdf));

                    $c->update([
                        'firmado_at' => now(),
                        'firmado_por' => $datos['nombre'],
                        'firmado_cargo' => $datos['cargo'],
                        'firmado_ip' => $request->ip(),
                        'firmado_user_agent' => substr((string) $request->userAgent(), 0, 500),
                        'hash_documento' => $hash,
                        'ruta_pdf' => $rutaPdf,
                        'ruta_firma' => $rutaFirma,
                    ]);
                });
            });
        } catch (DomainException $e) {
            if ($escribio) {
                $disco->delete([$rutaPdf, $rutaFirma]);
            }

            return back()->withErrors(['general' => $e->getMessage() . ' Comuníquese con su asesor.'])->withInput();
        } catch (\Throwable $e) {
            if ($escribio) {
                $disco->delete([$rutaPdf, $rutaFirma]);
            }
            report($e);

            return back()->withErrors(['general' => 'No se pudo registrar la firma. Intente de nuevo o comuníquese con su asesor.'])->withInput();
        }

        return redirect()->route('contratos.firma', $token)
            ->with('resultado', 'Gracias. El contrato fue firmado y su asesor dará seguimiento.');
    }
}

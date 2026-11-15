<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /** Verificación inicial de Meta: devuelve hub.challenge si el token coincide. */
    public function verificar(Request $request)
    {
        $esperado = (string) config('whatsapp.verify_token');
        $recibido = (string) $request->query('hub_verify_token', '');

        if ($esperado !== '' && $request->query('hub_mode') === 'subscribe' && hash_equals($esperado, $recibido)) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /** Eventos de Meta. Por ahora solo valida la firma y responde 200. */
    public function recibir(Request $request)
    {
        $secreto = (string) config('whatsapp.app_secret');
        if ($secreto === '') {
            return response('No configurado', 503);
        }

        $firma = (string) $request->header('X-Hub-Signature-256', '');
        $calculada = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secreto);

        if (! hash_equals($calculada, $firma)) {
            Log::warning('WhatsApp webhook: firma inválida');

            return response('Firma inválida', 403);
        }

        Log::info('WhatsApp webhook recibido (aún sin procesar)');

        return response('ok', 200);
    }
}

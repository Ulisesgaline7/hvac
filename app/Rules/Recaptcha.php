<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.recaptcha.secret');

        if (! $secret) {
            return; // sin llaves, el captcha queda desactivado
        }

        $ok = false;

        if (filled($value)) {
            try {
                $ok = (bool) Http::asForm()->timeout(5)->post(
                    'https://www.google.com/recaptcha/api/siteverify',
                    ['secret' => $secret, 'response' => $value, 'remoteip' => request()->ip()]
                )->json('success');
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (! $ok) {
            $fail('Confirma que no eres un robot e inténtalo de nuevo.');
        }
    }
}

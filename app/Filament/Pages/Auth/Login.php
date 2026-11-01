<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public ?string $captcha = null;

    public function authenticate(): ?LoginResponse
    {
        $secret = config('services.recaptcha.secret');

        if ($secret) {
            $ok = false;

            if (filled($this->captcha)) {
                try {
                    $ok = (bool) Http::asForm()->timeout(5)->post(
                        'https://www.google.com/recaptcha/api/siteverify',
                        ['secret' => $secret, 'response' => $this->captcha, 'remoteip' => request()->ip()]
                    )->json('success');
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            if (! $ok) {
                $this->captcha = null;
                $this->dispatch('recaptcha-reset');

                throw ValidationException::withMessages([
                    'data.email' => 'Confirma que no eres un robot e inténtalo de nuevo.',
                ]);
            }
        }

        return parent::authenticate();
    }
}

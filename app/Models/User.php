<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Support\Facades\Storage;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'firma_ruta', 'foto'])]
#[Hidden(['password', 'remember_token', 'firma_imagen'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasAnyRole(['administrador', 'coordinador']),
            'tecnico' => $this->hasRole('tecnico'),
            'portal' => $this->hasAnyRole(['cliente_admin', 'cliente_consulta']),
            default => false,
        };
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }
}

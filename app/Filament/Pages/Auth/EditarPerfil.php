<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class EditarPerfil extends EditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('foto')
                ->label('Foto de perfil')
                ->avatar()
                ->image()
                ->disk('public')
                ->directory('perfiles')
                ->visibility('public')
                ->maxSize(5120)
                ->imageResizeMode('cover')
                ->imageResizeTargetWidth('400')
                ->imageResizeTargetHeight('400'),
            FileUpload::make('firma_ruta')
                ->label('Firma (PNG con fondo transparente)')
                ->helperText('Se usa en los documentos PDF firmados por tu rol.')
                ->image()
                ->acceptedFileTypes(['image/png'])
                ->disk('local')
                ->directory('firmas/usuarios')
                ->visibility('private')
                ->maxSize(2048)
                ->getUploadedFileNameForStorageUsing(
                    fn ($file) => 'u' . auth()->id() . '-' . Str::random(8) . '.png'
                )
                ->deleteUploadedFileUsing(fn () => null)
                ->visible(fn () => ! auth()->user()->hasAnyRole(['cliente_admin', 'cliente_consulta'])),
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent()->required(fn () => (bool) auth()->user()->debe_cambiar_password),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
        ]);
    }

    protected function afterSave(): void
    {
        $u = $this->getUser();
        if ($u->wasChanged('password') && $u->debe_cambiar_password) {
            $u->forceFill(['debe_cambiar_password' => false])->save();
        }
    }
}

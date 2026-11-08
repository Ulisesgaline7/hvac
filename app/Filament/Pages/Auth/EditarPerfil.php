<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;

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
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
        ]);
    }
}

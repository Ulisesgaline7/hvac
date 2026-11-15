<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\AccesosUsuario;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(AccesosUsuario::class)->crear($data, $data['rol']);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Usuario creado. Los accesos se envían por correo.';
    }
}

<?php

namespace App\Filament\Admin\Resources\BeritaResource\Pages;

use App\Filament\Admin\Resources\BeritaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBerita extends CreateRecord
{
    protected static string $resource = BeritaResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
/** @var \App\Models\User $user */
    $user = Auth::user();
    $data['user_id'] = $user->id;
    $data['user_id'] = $user->id;
        return $data;
    }

}

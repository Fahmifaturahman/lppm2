<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\Dosen;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $user = $this->record;
        $nimNidn = $user->nim_nidn;

        if ($user->hasRole('mahasiswa')) {
            // Cek Mahasiswa berdasarkan NIM
            $mahasiswa = Mahasiswa::firstOrCreate(
                ['nim' => $nimNidn],
                ['nama' => $user->name]
            );

            if (is_null($mahasiswa->user_id)) {
                $mahasiswa->user_id = $user->id;
                $mahasiswa->save();
            }
        }

        if ($user->hasRole('dosen') || $user->hasRole('admin')) {
            // Cek Dosen berdasarkan NIDN
            $dosen = Dosen::firstOrCreate(
                ['nidn' => $nimNidn],
                ['nama' => $user->name]
            );

            if (is_null($dosen->user_id)) {
                $dosen->user_id = $user->id;
                $dosen->save();
            }
        }
    }
}

<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getFormSchema(): array
    {
        return [
            Section::make('Informasi Akun')
                ->description('Detail pengguna berdasarkan data yang ditautkan')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama')
                        ->disabled(),

                    TextInput::make('email')
                        ->label('Email')
                        ->disabled(),

                    TextInput::make('nim_nidn')
                        ->label('NIM / NIDN')
                        ->disabled(),

                    TextInput::make('pemilik_akun')
                        ->label('Tipe & Pemilik Akun')
                        ->disabled()
                        ->default(function () {
                            $user = $this->record;

                            if ($user->mahasiswa) {
                                return 'Mahasiswa: ' . $user->mahasiswa->nama;
                            }

                            if ($user->dosen) {
                                if ($user->hasRole('admin')) {
                                    return 'Dosen (Admin): ' . $user->dosen->nama;
                                }

                                return 'Dosen: ' . $user->dosen->nama;
                            }

                            if ($user->hasRole('admin')) {
                                return 'Admin (Non-Dosen)';
                            }

                            return 'Tidak Diketahui';
                        }),
                    TextInput::make('role_info')
                        ->label('Peran')
                        ->default(fn () => implode(', ', $this->record->getRoleNames()->toArray()))
                        ->disabled(),

                ])
                ->columns(2),
        ];
    }
}

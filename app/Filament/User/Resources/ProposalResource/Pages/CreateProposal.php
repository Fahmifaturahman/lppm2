<?php

namespace App\Filament\User\Resources\ProposalResource\Pages;

use App\Filament\User\Resources\ProposalResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProposal extends CreateRecord
{
    protected static string $resource = ProposalResource::class;

    /**
     * Method ini berjalan SEBELUM data disimpan ke database.
     * Tugasnya adalah memanipulasi data mentah dari form.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set 'user_id' (pemilik proposal) adalah user yang sedang login
        $data['user_id'] = Auth::id();
        
        return $data;
    }

    /**
     * Method ini berjalan SETELAH data proposal utama berhasil disimpan.
     * Tugasnya adalah menambahkan data Ketua ke tabel relasi anggota.
     */
    protected function afterCreate(): void
    {
        $proposal = $this->getRecord();
        
        /** @var User $user */
        $user = Auth::user();

        // Tentukan tipe user berdasarkan rolenya
        $tipe = $user->hasRole('mahasiswa') ? 'mahasiswa' : 'dosen';

        // Simpan data Ketua ke dalam relasi anggota
        $proposal->anggota()->create([
            'user_id'  => $user->id,
            'nama'   => $user->name,
            'peran'    => 'ketua',
            'tipe'     => $tipe,
            'nim_nidn' => $user->nim_nidn,
            'prodi'     => $user->prodi,
        ]);
    }
}
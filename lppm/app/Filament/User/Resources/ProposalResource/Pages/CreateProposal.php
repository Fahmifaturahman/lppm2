<?php

namespace App\Filament\User\Resources\ProposalResource\Pages;

use App\Filament\User\Resources\ProposalResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CreateProposal extends CreateRecord
{
    protected static string $resource = ProposalResource::class;

    // Simpan data pengaju (peran, bidang, dll)
    protected array $pengajuData = [];

    /**
     * Memproses data form sebelum disimpan ke tabel `proposals`
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Simpan file jika bentuknya array
        if (is_array($data['file'])) {
            $data['file'] = collect($data['file'])->first();
        }

        // Simpan data pengaju dari form sebelum dihapus
        $this->pengajuData = [
            'peran' => $data['peran'] ?? null,
            'bidang_fokus' => $data['bidang_fokus'] ?? null,
            'uraian_tugas' => $data['uraian_tugas'] ?? null,
            'rumpun_ilmu_lv2' => $data['rumpun_ilmu_lv2'] ?? null,
        ];

        // Tambahkan ID user yang membuat proposal
        $data['user_id'] = Auth::id();

        // Hapus field yang tidak termasuk ke tabel `proposals`
        unset(
            $data['peran'],
            $data['bidang_fokus'],
            $data['uraian_tugas'],
            $data['rumpun_ilmu_lv2']
        );

        return $data;
    }

    /**
     * Setelah proposal berhasil dibuat, simpan data pengaju dan anggota ke tabel `proposal_anggota`
     */
    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $proposal = $this->record;

        /** @var User $user */
        $user = Auth::user();

        // Simpan data pengaju sebagai anggota (peran bisa ketua/anggota)
        $proposal->anggota()->create([
            'user_id' => $user->id,
            'nama' => $user->name,
            'nim_nidn' => $user->mahasiswa->nim ?? $user->dosen->nidn ?? null,
            'tipe' => $user->hasRole('mahasiswa') ? 'mahasiswa' : 'dosen',
            'prodi' => $user->mahasiswa->prodi ??  $user->dosen->prodi ??  null,
            'peran' => $this->pengajuData['peran'] ?? 'anggota',
            'bidang_fokus' => $this->pengajuData['bidang_fokus'] ?? null,
            'uraian_tugas' => $this->pengajuData['uraian_tugas'] ?? null,
            'rumpun_ilmu_lv2' => $this->pengajuData['rumpun_ilmu_lv2'] ?? null,
        ]);

        // Simpan anggota tambahan dari repeater
        foreach ($data['anggota'] ?? [] as $anggota) {
            $proposal->anggota()->create($anggota);
        }
    }
}

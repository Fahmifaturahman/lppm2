{{-- resources/views/filament/infolists/proposal-details.blade.php (SESUAIKAN NAMA FILE LO) --}}

@php
    // Definisi Class CSS (Ini tetep di sini karena spesifik untuk view ini)
    $tableClasses = 'w-full text-left table-auto divide-y divide-gray-200 dark:divide-white/5';
    $thClasses = 'px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 text-left';
    $tdClasses = 'px-3 py-2 text-sm text-gray-950 dark:text-white';

    // Eager load relasi yang dibutuhkan untuk view ini
    // Relasi 'anggotaDosen' dan 'anggotaMahasiswa' sudah me-load 'user' di dalamnya
    $record->loadMissing(['anggotaDosen', 'anggotaMahasiswa', 'luaran']); 

    // Info Ketua Tim
    $ketua = $record->user;
    $ketuaTipeLabel = 'NIM / NIDN'; 
    if ($ketua) {
        if ($ketua->hasRole('dosen')) {
            $ketuaTipeLabel = 'NIDN';
        } elseif ($ketua->hasRole('mahasiswa')) {
            $ketuaTipeLabel = 'NIM';
        }
    }
@endphp

<div class="mb-6">
    <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white mb-2">
        Ketua Tim
    </h3>
    
    <div class="p-4 rounded-lg bg-gray-50 dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10">
        <dl class="grid grid-cols-1 md:grid-cols-4 gap-y-2">
            
            <div class="fi-in-text-entry text-sm">
                <dt class="font-medium text-gray-500 dark:text-gray-400">Nama</dt>
                <dd class="text-gray-950 dark:text-white">{{ $ketua->name ?? 'N/A' }}</dd>
            </div>

            <div class="fi-in-text-entry text-sm">
                <dt class="font-medium text-gray-500 dark:text-gray-400">{{ $ketuaTipeLabel }}</dt>
                <dd class="text-gray-950 dark:text-white">{{ $ketua->nim_nidn ?? 'N/A' }}</dd>
            </div>

            <div class="fi-in-text-entry text-sm">
                <dt class="font-medium text-gray-500 dark:text-gray-400">Program Studi</dt>
                <dd class="text-gray-950 dark:text-white">{{ $ketua->prodi ?? 'N/A' }}</dd>
            </div>

            <div class="fi-in-text-entry text-sm">
                <dt class="font-medium text-gray-500 dark:text-gray-400">Peran</dt>
                <dd>
                    <span class="fi-badge fi-badge-color-primary inline-flex items-center gap-x-1.5 rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-primary-500/10 text-primary-700 ring-primary-500/20 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-500/20">
                        Ketua
                    </span>
                </dd>
            </div>

        </dl>
    </div>
</div>

@if ($record->anggotaDosen->isNotEmpty())
    <div class="mb-6">
        <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white mb-2">
            Anggota Dosen
        </h3>
        {{-- Panggil partial view untuk Anggota Dosen --}}
        @include('filament.infolists.components._anggota-table', [
            'anggotaList' => $record->anggotaDosen,
            'kategori' => $record->kategori,
            'tipe' => 'dosen',
            'tableClasses' => $tableClasses,
            'thClasses' => $thClasses,
            'tdClasses' => $tdClasses,
        ])
    </div>
@endif

@if ($record->anggotaMahasiswa->isNotEmpty())
    <div>
        <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white mb-2">
            Anggota Mahasiswa
        </h3>
        {{-- Panggil partial view untuk Anggota Mahasiswa --}}
        @include('filament.infolists.components._anggota-table', [
            'anggotaList' => $record->anggotaMahasiswa,
            'kategori' => $record->kategori,
            'tipe' => 'mahasiswa',
            'tableClasses' => $tableClasses,
            'thClasses' => $thClasses,
            'tdClasses' => $tdClasses,
        ])
    </div>
@endif

@if ($record->anggotaDosen->isEmpty() && $record->anggotaMahasiswa->isEmpty())
    <div class="fi-in-text-entry-value text-sm text-gray-500 dark:text-gray-400">
        Proposal ini tidak memiliki anggota selain ketua.
    </div>
@endif

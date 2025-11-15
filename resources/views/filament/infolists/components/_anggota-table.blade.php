{{-- resources/views/filament/infolists/partials/_anggota-table.blade.php --}}
{{-- 
    File ini menerima variabel: 
    - $anggotaList (Collection dari anggota dosen atau mahasiswa)
    - $kategori (String, dari $record->kategori)
    - $tipe (String: 'dosen' atau 'mahasiswa')
    - $tableClasses, $thClasses, $tdClasses (Class CSS)
--}}

<div class="rounded-lg ring-1 ring-gray-200 dark:ring-white/10 overflow-hidden">
    <div class="overflow-x-auto">
        
        {{-- ===== FIX 1: Tambahkan 'w-full' ===== --}}
        {{-- Ini maksa tabel buat ngisi lebar kontainer, nggak lebih. --}}
        <table class="w-full {{ $tableClasses }}">
            
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="{{ $thClasses }} whitespace-nowrap" style="width: 1%;">No.</th>
                    <th class="{{ $thClasses }} whitespace-nowrap">{{ $tipe === 'dosen' ? 'NIDN' : 'NIM' }}</th> 
                    <th class="{{ $thClasses }} whitespace-nowrap">Nama Lengkap</th>
                    <th class="{{ $thClasses }} whitespace-nowrap">Prodi</th>
                    
                    @if (in_array($kategori, ['Penelitian', 'Penelitian & Pengabdian']))
                        <th class="{{ $thClasses }} whitespace-nowrap">Bidang Fokus</th>
                    @elseif (in_array($kategori, ['Pengabdian', 'Penelitian & Pengabdian']))
                        <th class="{{ $thClasses }} whitespace-nowrap">Uraian Tugas</th>
                    @endif
                    
                    {{-- Kolom 'File Tambahan' hanya untuk Dosen --}}
                    @if ($tipe === 'dosen')
                        <th class="{{ $thClasses }} whitespace-nowrap">File Tambahan</th>
                    @endif
                    <th class="{{ $thClasses }} whitespace-nowrap">Peran</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                @foreach ($anggotaList as $anggota)
                    <tr>
                        <td class="{{ $tdClasses }}">{{ $loop->iteration }}</td>
                        <td class="{{ $tdClasses }} whitespace-nowrap">{{ $anggota->nomorInduk }}</td>
                        
                        {{-- ===== FIX 2: Hapus 'whitespace-nowrap' ===== --}}
                        {{-- Biar nama yang panjang bisa ganti baris (wrap). --}}
                        <td class="{{ $tdClasses }} whitespace-nowrap">{{ $anggota->namaLengkap }}</td>
                        
                        <td class="{{ $tdClasses }} whitespace-nowrap">{{ $anggota->prodiAnggota }}</td>
                        
                        @if (in_array($kategori, ['Penelitian', 'Penelitian & Pengabdian']))
                            
                            {{-- ===== FIX 3: Tambahkan 'break-all' ===== --}}
                            {{-- Ini 'maksa' teks panjang tanpa spasi (kayak di screenshot) buat ganti baris. --}}
                            <td class="{{ $tdClasses }} break-all">{{ $anggota->bidang_fokus ?? '-' }}</td>
                        
                        @elseif (in_array($kategori, ['Pengabdian', 'Penelitian & Pengabdian']))
                            
                            {{-- ===== FIX 4: Tambahkan 'break-words' (Best practice) ===== --}}
                            {{-- Ini buat ganti baris di antara kata (kalau berupa kalimat). --}}
                            <td class="{{ $tdClasses }} break-words">{{ $anggota->uraian_tugas ?? '-' }}</td>
                        
                        @endif

                        {{-- Data 'File Tambahan' hanya untuk Dosen --}}
                        @if ($tipe === 'dosen')
                        <td class="{{ $tdClasses }}">
                            @if ($anggota->file_tambahan)
                                <a href="{{ Storage::url($anggota->file_tambahan) }}" target="_blank" 
                                class="text-primary-600 hover:text-primary-500 font-medium">
                                    Download
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        @endif
                        <td class="{{ $tdClasses }}">{{ $anggota->peran }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
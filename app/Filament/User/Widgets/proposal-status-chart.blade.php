<x-filament::widget>
    <x-filament::card>
        {{-- Chart --}}
        {{ $this->chart }}

        {{-- Ringkasan status proposal --}}
        <div class="mt-6">
            <h3 class="text-base font-semibold mb-2">📊 Ringkasan Status per Tanggal</h3>
            <div class="space-y-3">
                @foreach ($ringkasan as $tanggal => $status)
                    <div class="text-sm bg-gray-50 rounded px-3 py-2 shadow-sm">
                        <div class="font-medium text-gray-800">
                            📅 {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
                        </div>
                        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-gray-700">
                            🟨 Menunggu: {{ $status['menunggu'] ?? 0 }}
                            🟩 Diterima: {{ $status['diterima'] ?? 0 }}
                            🟥 Ditolak: {{ $status['ditolak'] ?? 0 }}
                            ⬜ Revisi: {{ $status['revisi'] ?? 0 }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>

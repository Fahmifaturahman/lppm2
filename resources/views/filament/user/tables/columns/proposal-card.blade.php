@php
    $proposal = $getRecord();
    $statusColor = match ($proposal->status) {
        'menunggu' => 'bg-yellow-500/10 text-yellow-700',
        'diterima' => 'bg-green-500/10 text-green-700',
        'revisi' => 'bg-blue-500/10 text-blue-700',
        'ditolak' => 'bg-red-500/10 text-red-700',
        default => 'bg-gray-500/10 text-gray-700',
    };
    $peran = ($proposal->user_id === auth()->id()) ? 'Ketua' : 'Anggota';
    $peranColor = ($peran === 'Ketua') ? 'bg-sky-500/10 text-sky-700' : 'bg-gray-500/10 text-gray-700';
@endphp

<div class="p-4 space-y-3 bg-white border border-gray-200 rounded-lg shadow-sm">
    <div class="flex items-center space-x-2">
        <span @class([
            'px-2 py-1 text-xs font-medium rounded-md',
            $statusColor,
        ])>
            {{ str()->title($proposal->status) }}
        </span>
        <span @class([
            'px-2 py-1 text-xs font-medium rounded-md',
            $peranColor,
        ])>
            {{ $peran }}
        </span>
    </div>

    <h3 class="text-lg font-bold text-gray-800">
        {{ $proposal->judul }}
    </h3>

    <div class="flex items-center space-x-2 text-sm text-gray-500">
        <span>{{ $proposal->kategori }}</span>
        <span>·</span>
        <div class="flex items-center">
            @svg('heroicon-o-calendar-days', 'w-4 h-4 mr-1')
            <span>{{ $proposal->tahun_pelaksanaan }}</span>
        </div>
    </div>
</div>
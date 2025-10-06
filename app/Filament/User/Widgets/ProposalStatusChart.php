<?php

namespace App\Filament\User\Widgets;

use App\Models\Proposal;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ProposalStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Statistik Proposal';
    public ?string $filter = 'month';
    public array $ringkasanStatusPerTanggal = [];

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Harian',
            'month' => 'Bulanan',
            'year' => 'Tahunan',
        ];
    }

    protected function getData(): array
    {
        $userId = Auth::id();

        $labelFormat = match ($this->filter) {
            'day' => "TO_CHAR(created_at, 'Day, DD Mon YYYY')",
            'month' => "TO_CHAR(created_at, 'Month YYYY')",
            'year' => "EXTRACT(YEAR FROM created_at)::TEXT",
        };

        $rawData = Proposal::where('user_id', $userId)
            ->selectRaw("$labelFormat as label, status, COUNT(*) as total")
            ->groupByRaw("label, status")
            ->orderByRaw("MIN(created_at)")
            ->get();

        $statuses = [
            'menunggu' => ['label' => 'Menunggu', 'color' => '#facc15'],
            'diterima' => ['label' => 'Diterima', 'color' => '#22c55e'],
            'ditolak'  => ['label' => 'Ditolak',  'color' => '#ef4444'],
            'revisi'   => ['label' => 'Revisi',   'color' => '#a3a3a3'],
        ];

        $labels = $rawData->pluck('label')->map(fn($l) => trim($l))->unique()->values();
        $translatedLabels = $this->translateLabels($labels);

        $grouped = $rawData->groupBy(fn ($item) => trim($item->label));

        $datasets = [];
        foreach ($statuses as $statusKey => $info) {
            $datasets[] = [
                'label' => $info['label'],
                'data' => $labels->map(fn($label) =>
                    optional($grouped->get($label)?->firstWhere('status', $statusKey))->total ?? 0
                ),
                'backgroundColor' => $info['color'],
            ];
        }

        $this->ringkasanStatusPerTanggal = $translatedLabels->mapWithKeys(function ($label, $i) use ($labels, $grouped, $statuses) {
            $originalLabel = $labels[$i];
            $summary = [];
            foreach (array_keys($statuses) as $statusKey) {
                $summary[$statusKey] = optional($grouped->get($originalLabel)?->firstWhere('status', $statusKey))->total ?? 0;
            }
            return [$label => $summary];
        })->toArray();

        return [
            'labels' => $translatedLabels,
            'datasets' => $datasets,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'plugins' => [
                'legend' => ['position' => 'bottom'],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0, 'stepSize' => 1],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getView(): string
    {
        return 'filament.user.widgets.proposal-status-chart';
    }

    protected function getViewData(): array
    {
        return [
            'ringkasan' => $this->ringkasanStatusPerTanggal,
        ];
    }

    protected function translateLabels($labels): \Illuminate\Support\Collection
    {
        $hariInggris = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $hariIndonesia = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

        $bulanInggris = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $bulanIndonesia = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

        return $labels->map(function ($label) use ($hariInggris, $hariIndonesia, $bulanInggris, $bulanIndonesia) {
            $translated = str_replace($hariInggris, $hariIndonesia, $label);
            $translated = str_replace($bulanInggris, $bulanIndonesia, $translated);
            return trim($translated);
        });
    }
}

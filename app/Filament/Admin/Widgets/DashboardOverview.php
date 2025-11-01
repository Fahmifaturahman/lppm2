<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Proposal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\AccountWidget;

class DashboardOverview extends BaseWidget
{
    public static function getWidgets(): array
    {
        return [
            AccountWidget::class,
            static::class,
        ];
    }

    protected function getCards(): array
    {
        return [
            Stat::make('Total Proposal', Proposal::count())
                ->description('Semua proposal yang diajukan')
                ->color('primary'),

            Stat::make('Proposal Diterima', Proposal::where('status', 'diterima')->count())
                ->description('Proposal yang telah disetujui')
                ->color('success'),

            Stat::make('Proposal Menunggu', Proposal::where('status', 'menunggu')->count())
                ->description('Menunggu validasi')
                ->color('warning'),

            Stat::make('Proposal Ditolak', Proposal::where('status', 'ditolak')->count())
                ->description('Perlu ditinjau ulang')
                ->color('danger'),

            Stat::make('Proposal Revisi', Proposal::where('status', 'revisi')->count())
                ->description('Perlu perbaikan dari pengusul')
                ->color('gray'),
        ];
    }

    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';
}

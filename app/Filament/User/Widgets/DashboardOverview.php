<?php

namespace App\Filament\User\Widgets;

use App\Models\Proposal;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\Facades\Auth;

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
        /** @var \App\Models\User $user */
        $userId = Auth::id();
        return [
            Card::make('Total Proposal', Proposal::where('user_id', $userId)->count())
                ->description('Semua proposal yang diajukan')
                ->color('primary'),

            Card::make('Proposal Diterima', Proposal::where('user_id', $userId)->where('status', 'diterima')->count())
                ->description('Proposal yang telah disetujui')
                ->color('success'),

            Card::make('Proposal Menunggu', Proposal::where('user_id', $userId)->where('status', 'menunggu')->count())
                ->description('Menunggu validasi')
                ->color('warning'),

            Card::make('Proposal Ditolak', Proposal::where('user_id', $userId)->where('status', 'ditolak')->count())
                ->description('Perlu ditinjau ulang')
                ->color('danger'),
            Card::make('Proposal Revisi', Proposal::where('user_id', $userId)->where('status', 'revisi')->count())
                ->description('Perlu perbaikan dari pengusul')
                ->color('gray'),
        ];
    }
}

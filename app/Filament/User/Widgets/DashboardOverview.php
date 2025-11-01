<?php

namespace App\Filament\User\Widgets;

use App\Models\Proposal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\AccountWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DashboardOverview extends BaseWidget
{
    /**
     * Mengatur widget apa saja yang tampil di halaman ini.
     */
    public static function getWidgets(): array
    {
        return [
            AccountWidget::class, 
            static::class,      
        ];
    }

    /**
     * Mengambil dan menghitung data untuk ditampilkan di kartu statistik.
     */
    protected function getCards(): array
    {
        $user = Auth::user();

        
        $query = Proposal::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereHas('anggota', function (Builder $subQuery) use ($user) {
                          $subQuery->where('user_id', $user->id);
                      });
            });

        return [
            Stat::make('Total Proposal', $query->clone()->count())
                ->description('Semua proposal yang melibatkan Anda')
                ->color('primary'),

            Stat::make('Proposal Diterima', $query->clone()->where('status', 'diterima')->count())
                ->description('Proposal yang telah disetujui')
                ->color('success'),

            Stat::make('Proposal Menunggu', $query->clone()->where('status', 'menunggu')->count())
                ->description('Menunggu validasi')
                ->color('warning'),

            Stat::make('Proposal Ditolak', $query->clone()->where('status', 'ditolak')->count())
                ->description('Perlu ditinjau ulang')
                ->color('danger'),
                
            Stat::make('Proposal Revisi', $query->clone()->where('status', 'revisi')->count())
                ->description('Perlu perbaikan dari pengusul')
                ->color('info'),
        ];
    }
}
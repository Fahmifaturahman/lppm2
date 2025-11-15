<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProposalResource\Pages; 
use App\Models\Proposal;
use App\Models\ProposalLuaran;
use App\Models\User;
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Forms; 
use Filament\Forms\Form; 
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea as FormTextarea; 
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Actions\Action as TableAction; 
use Filament\Infolists\Components\TextEntry\TextEntrySize;

class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $label = 'Manajemen Proposal'; 
    protected static ?string $pluralLabel = 'Manajemen Proposal'; 
    protected static ?string $navigationGroup = 'Proposal'; 

    
    public static function canCreate(): bool
    {
        return false; 
    }

    
    public static function canEdit(Model $record): bool
    {
        
        return false; 
    }

    
    public static function canDelete(Model $record): bool
    {
        return true; 
    }

    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user', 'anggota.user', 'luaran']); 
    }

    
    /*
     * =================================================================
     * METHOD FORM() DIHAPUS SEMUA
     * Admin tidak lagi mengedit proposal melalui form utama.
     * Semua aksi (Setujui, Tolak, Revisi) dilakukan via Tombol Actions.
     * =================================================================
     */

    
    public static function infolist(Infolist $infolist): Infolist
    {
        
        
        return $infolist->schema([
            Grid::make(3)
                ->schema([
                    Grid::make(1)
                        ->schema([
                            Section::make('Detail Proposal')
                                ->icon('heroicon-o-clipboard-document-list')
                                ->columns(2)
                                ->schema([
                                    
                                    TextEntry::make('user.name')->label('Ketua Pengusul')
                                        ->icon('heroicon-o-user')
                                        ->size(TextEntrySize::Large)
                                        ->columnSpanFull(),
                                    TextEntry::make('judul')->label('Judul Proposal')->columnSpanFull(),
                                    TextEntry::make('status')->label('Status Saat Ini')->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'menunggu' => 'warning', 'diterima' => 'success',
                                            'revisi'   => 'info', 'ditolak'   => 'danger',
                                            default    => 'gray',
                                        }),
                                    TextEntry::make('kategori')->badge(),
                                    TextEntry::make('tahun_pelaksanaan')->label('Tahun'),
                                    TextEntry::make('semester')->badge(),
                                    TextEntry::make('ringkasan')->columnSpanFull()->markdown(),
                                ]),

                            Section::make('Tim Proposal')
                                ->icon('heroicon-o-users')
                                ->schema([
                                    \Filament\Infolists\Components\ViewEntry::make('anggota')
                                        ->hiddenLabel()
                                        
                                        ->view('filament.infolists.components.proposal-details') 
                                        ->viewData([
                                            'record' => $infolist->getRecord(),
                                        ])
                                ]),
                            
                            Section::make('Daftar Luaran Proposal')
                                ->icon('heroicon-o-document-arrow-up')
                                ->collapsible()
                                ->schema([
                                    RepeatableEntry::make('luaran')
                                        ->hiddenLabel()
                                        ->columns(2)
                                        ->schema([
                                            TextEntry::make('jenis_luaran')->label('Jenis')->badge()
                                                ->formatStateUsing(fn (string $state): string => 
                                                    match($state) {
                                                        'jurnal_nasional' => 'Jurnal Nasional',
                                                        'jurnal_internasional' => 'Jurnal Internasional',
                                                        'buku_ajar' => 'Buku Ajar',
                                                        'paten' => 'Paten',
                                                        'hki' => 'HKI',
                                                        'lainnya' => 'Lainnya',
                                                        default => $state
                                                    }
                                                )
                                                ->columnSpan(1),
                                            TextEntry::make('status')->label('Status Pengerjaan')->badge()
                                                ->formatStateUsing(fn (string $state): string => 
                                                    match($state) {
                                                        'belum_dimulai' => 'Belum Dimulai',
                                                        'dalam_pengerjaan' => 'Dalam Pengerjaan',
                                                        'selesai' => 'Selesai',
                                                        'dibatalkan' => 'Dibatalkan',
                                                        default => 'Lainnya'
                                                    }
                                                )
                                                ->color(fn (string $state): string => 
                                                    match($state) {
                                                        'belum_dimulai' => 'gray',
                                                        'dalam_pengerjaan' => 'warning',
                                                        'selesai' => 'success',
                                                        'dibatalkan' => 'danger',
                                                        default => 'gray'
                                                    }
                                                )
                                                ->columnSpan(1),

                                            TextEntry::make('verifikasi_status')
                                                ->label('Status Verifikasi Admin')
                                                ->badge()
                                                ->formatStateUsing(fn (?string $state) => $state ? ucwords(str_replace('_', ' ', $state)) : 'Belum Dinilai')
                                                ->color(fn (?string $state = null): string => 
                                                    match($state) {
                                                        'disetujui' => 'success',
                                                        'ditolak' => 'danger',
                                                        default => 'gray'
                                                    }
                                                )
                                                ->columnSpanFull(),
                                            TextEntry::make('verifikasi_catatan')
                                                ->label('Catatan Verifikasi Luaran Admin')
                                                ->icon('heroicon-o-chat-bubble-left-right')
                                                ->markdown()
                                                ->color('danger')
                                                ->visible(fn ($record) => $record->verifikasi_status === 'ditolak' && filled($record->verifikasi_catatan))
                                                ->columnSpanFull(),

                                            TextEntry::make('tujuan')
                                                ->label('Tujuan')
                                                ->markdown()
                                                ->columnSpanFull(),
                                            TextEntry::make('deskripsi')
                                                ->label('Deskripsi')
                                                ->markdown()
                                                ->columnSpanFull(),
                                            
                                            InfolistActions::make([
                                                InfolistAction::make('downloadLuaran')
                                                    ->label('Download File')
                                                    ->icon('heroicon-o-arrow-down-tray')
                                                    ->color('gray')
                                                    ->url(fn (ProposalLuaran $record) => $record->file ? Storage::url($record->file) : '#', true)
                                                    ->visible(fn (ProposalLuaran $record) => filled($record->file)),
                                                
                                                
                                                InfolistAction::make('verifikasiLuaran')
                                                    ->label('Verifikasi Luaran Ini')
                                                    ->icon('heroicon-o-check-badge')
                                                    ->form([
                                                        Select::make('verifikasi_status')
                                                            ->label('Status Verifikasi')
                                                            ->options([
                                                                'disetujui' => 'Disetujui',
                                                                'ditolak' => 'Ditolak',
                                                            ])
                                                            ->required(),
                                                        FormTextarea::make('verifikasi_catatan')
                                                            ->label('Catatan Verifikasi')
                                                            ->rows(5)
                                                            ->visible(fn ($get) => $get('verifikasi_status') === 'ditolak'),
                                                    ])
                                                    ->action(function (ProposalLuaran $record, array $data) {
                                                        $record->update([
                                                            'verifikasi_status' => $data['verifikasi_status'],
                                                            'verifikasi_catatan' => $data['verifikasi_catatan'] ?? null,
                                                        ]);
                                                        Notification::make()->title('Status luaran diperbarui')->success()->send();
                                                    })
                                                    ->fill(fn (ProposalLuaran $record) => $record->only(['verifikasi_status', 'verifikasi_catatan']))
                                            ])->fullWidth(),
                                        ]),
                                ]),
                        ])
                        ->columnSpan(2),

                    Grid::make(1)
                        ->schema([
                            
                            Section::make('Aksi Reviewer')
                                ->icon('heroicon-o-academic-cap')
                                ->schema([
                                    TextEntry::make('catatan')
                                        ->label('Catatan Review Terakhir')
                                        ->markdown()
                                        ->icon('heroicon-o-chat-bubble-left-right')
                                        ->color('warning')
                                        ->visible(fn (Proposal $record) => ($record->status === 'revisi' || $record->status === 'ditolak') && filled($record->catakatan)), 

                                    InfolistActions::make([
                                        InfolistAction::make('setujui')
                                            ->label('Setujui Proposal')
                                            ->icon('heroicon-o-check-circle')
                                            ->color('success')
                                            ->requiresConfirmation()
                                            ->visible(fn(Proposal $record) => in_array($record->status, ['menunggu', 'revisi']))
                                            ->action(function (Proposal $record) {
                                                $record->update([
                                                    'status' => 'diterima',
                                                    'catatan' => 'Selamat, proposal Anda telah disetujui.'
                                                ]);
                                                Notification::make()->title('Proposal disetujui')->success()->send();
                                            }),
                                        
                                        InfolistAction::make('mintaRevisi')
                                            ->label('Minta Revisi')
                                            ->icon('heroicon-o-arrow-uturn-left')
                                            ->color('info')
                                            ->visible(fn(Proposal $record) => in_array($record->status, ['menunggu', 'diterima']))
                                            ->form([
                                                FormTextarea::make('catatan_revisi')
                                                    ->label('Catatan untuk Revisi')
                                                    ->rows(10)
                                                    ->required()
                                                    ->default(fn(Proposal $record) => $record->catatan),
                                            ])
                                            ->action(function (Proposal $record, array $data) {
                                                $record->update([
                                                    'status' => 'revisi',
                                                    'catatan' => $data['catatan_revisi']
                                                ]);
                                                Notification::make()->title('Proposal dikembalikan untuk revisi')->info()->send();
                                            }),
                                        
                                        InfolistAction::make('tolak')
                                            ->label('Tolak Proposal')
                                            ->icon('heroicon-o-x-circle')
                                            ->color('danger')
                                            ->requiresConfirmation()
                                            ->visible(fn(Proposal $record) => in_array($record->status, ['menunggu', 'revisi']))
                                            ->form([
                                                FormTextarea::make('catatan_penolakan')
                                                    ->label('Alasan Penolakan')
                                                    ->rows(10)
                                                    ->required(),
                                            ])
                                            ->action(function (Proposal $record, array $data) {
                                                $record->update([
                                                    'status' => 'ditolak',
                                                    'catatan' => $data['catatan_penolakan']
                                                ]);
                                                Notification::make()->title('Proposal ditolak')->danger()->send();
                                            }),
                                        
                                        InfolistAction::make('downloadFile')
                                            ->label('Download Proposal')
                                            ->icon('heroicon-o-arrow-down-tray')->color('gray')
                                            ->url(fn (Proposal $record): string => $record->file ? route('proposals.download', $record) : '')
                                            ->openUrlInNewTab()
                                            ->visible(fn (Proposal $record) => filled($record->file)),

                                    ])->fullWidth(),
                                ]),
                        ])
                        ->columnSpan(1),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('judul')->searchable()->weight('bold')
                    ->description(fn (Proposal $record): string => "Kategori: {$record->kategori}"),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Ketua Pengusul')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tahun_pelaksanaan')
                    ->label('Tahun')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'menunggu' => 'warning', 'diterima' => 'success',
                        'revisi' => 'info', 'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->description(function (Proposal $record) {
                        return match ($record->status) {
                            'menunggu' => 'Perlu direview.',
                            'revisi'   => 'Menunggu perbaikan dari user.',
                            'diterima' => 'Proposal disetujui.',
                            'ditolak'  => 'Proposal ditolak.',
                            default    => '',
                        };
                    }),
            ])
            ->filters([
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'revisi' => 'Revisi', 
                        'diterima' => 'Diterima', 
                        'ditolak' => 'Ditolak'])
                    ->multiple()
                    ->label('Status Proposal'),
                Tables\Filters\SelectFilter::make('kategori')
                    ->options([
                        'Penelitian' => 'Penelitian', 
                        'Pengabdian' => 'Pengabdian',
                        'Penelitian & Pengabdian' => 'Penelitian & Pengabdian'])
                    ->multiple()
                    ->label('Kategori Proposal'),
                
                Tables\Filters\SelectFilter::make('tahun_pelaksanaan')
                    ->label('Tahun Pelaksanaan')
                    ->options(fn () => Proposal::query()
                        ->distinct()
                        ->orderBy('tahun_pelaksanaan', 'desc')
                        ->pluck('tahun_pelaksanaan', 'tahun_pelaksanaan')
                        ->toArray())
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('semester')
                    ->options([
                        'Ganjil' => 'Ganjil',
                        'Genap' => 'Genap'])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Ketua Pengusul')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('anggota_tim')
                    ->label('Anggota Tim')
                     ->form([
                        Forms\Components\Select::make('user_id_anggota')
                            ->label('Cari Nama Anggota')
                            ->options(
                                    User::query()
                                    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['dosen', 'mahasiswa']))
                                    ->pluck('name', 'id')
                                )
                            ->searchable()
                            ->preload()
                            ->multiple(),
                        ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['user_id_anggota'])) {
                            return $query;
                        }
                        return $query->whereHas('anggota', function (Builder $subQuery) use ($data) {
                                $subQuery->whereIn('user_id', $data['user_id_anggota']);
                         });
                    })
                    ->indicateUsing(function (array $data): ?string {
                         if (empty($data['user_id_anggota'])) {
                            return null;
                        }
                        $count = count($data['user_id_anggota']);
                        return "Memiliki {$count} anggota tim";
                        }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->infolist(static::infolist(...)), 
                    
                    
                    
                    
                    TableAction::make('setujui')
                        ->label('Setujui')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(Proposal $record) => in_array($record->status, ['menunggu', 'revisi']))
                        ->action(fn (Proposal $record) => $record->update(['status' => 'diterima', 'catatan' => 'Selamat, proposal Anda telah disetujui.']))
                        ->after(fn () => Notification::make()->title('Proposal disetujui')->success()->send()),
                    
                    TableAction::make('mintaRevisi')
                        ->label('Revisi')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('info')
                        ->visible(fn(Proposal $record) => in_array($record->status, ['menunggu', 'diterima']))
                        ->form([
                            FormTextarea::make('catatan_revisi')
                                ->label('Catatan untuk Revisi')
                                ->rows(10)
                                ->required()
                                ->default(fn(Proposal $record) => $record->catatan),
                        ])
                        ->action(fn (Proposal $record, array $data) => $record->update(['status' => 'revisi', 'catatan' => $data['catatan_revisi']]))
                        ->after(fn () => Notification::make()->title('Proposal dikembalikan untuk revisi')->info()->send()),

                    TableAction::make('tolak')
                        ->label('Ditolak')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(Proposal $record) => in_array($record->status, ['menunggu', 'revisi']))
                        ->form([
                            FormTextarea::make('catatan_penolakan')
                                ->label('Alasan Penolakan')
                                ->rows(10)
                                ->required(),
                        ])
                        ->action(fn (Proposal $record, array $data) => $record->update(['status' => 'ditolak', 'catatan' => $data['catatan_penolakan']]))
                        ->after(fn () => Notification::make()->title('Proposal ditolak')->danger()->send()),
                    
                    Tables\Actions\DeleteAction::make()->requiresConfirmation(), 
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProposals::route('/'), 
            'view' => Pages\ViewProposal::route('/{record}'), 
            
        ];
    }
}
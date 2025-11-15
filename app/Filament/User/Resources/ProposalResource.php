<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\ProposalResource\Pages;
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
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $label = 'Daftar Proposal';
    protected static ?string $pluralLabel = 'Daftar Proposal';

    public static function canEdit(Model $record): bool
    {
        if (!$record instanceof Proposal) {
            return false;
        }

        $isOwner = Auth::id() === $record->user_id;
        if (!$isOwner) {
            return false;
        }
        $isRevisi = $record->status === 'revisi';
        $isNotComplete = !$record->is_complete;
        return $isRevisi || $isNotComplete;
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::id() === $record->user_id && $record->status === 'ditolak';
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = parent::getEloquentQuery();
        $query->with(['user'])
            ->withCount('anggota')
            ->withExists([
                'anggota as is_anggota' => function (Builder $subQuery) use ($user){
                    $subQuery->where('user_id', $user->id);
                }
            ]);
            if ($user->hasRole('admin')){
                return $query;
            }

            $query->where(function (Builder $q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('anggota', function (Builder $subQuery) use ($user) {
                        $subQuery->where('user_id', $user->id);
                    });
            });
        return $query;

    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Utama Proposal')
                ->description('Isi detail utama dari proposal penelitian atau pengabdian Anda.')
                ->schema([
                    Forms\Components\TextInput::make('judul')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('ringkasan')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),
                    Forms\Components\Select::make('kategori')
                        ->label('Kategori Proposal')
                        ->options(['Penelitian' => 'Penelitian', 'Pengabdian' => 'Pengabdian', 'Penelitian & Pengabdian' => 'Penelitian & Pengabdian'])
                        ->required()->reactive(),
                    Forms\Components\TextInput::make('tahun_pelaksanaan')
                        ->numeric()
                        ->label('Tahun Pelaksanaan')
                        ->minValue(2020)
                        ->maxValue(2200)
                        ->required(),
                    Forms\Components\Select::make('semester')
                        ->label('Semester Pelaksanaan')
                        ->options([
                            'Ganjil' => 'Ganjil',
                            'Genap' => 'Genap',
                            'Pendek' => 'Pendek',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('bidang_fokus')
                        ->label('Bidang Fokus')
                        ->nullable()
                        ->visible(fn ($get) => in_array($get('kategori'), ['Penelitian', 'Penelitian & Pengabdian'])),
                    Forms\Components\Textarea::make('uraian_tugas')->label('Uraian Tugas')
                        ->rows(3)
                        ->nullable()
                        ->visible(fn ($get) => in_array($get('kategori'), ['Pengabdian', 'Penelitian & Pengabdian'])),                    
                    Forms\Components\FileUpload::make('file')->label('File Proposal (PDF)')
                        ->disk('public')
                        ->directory('proposals')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)
                        ->required()
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Repeater::make('anggota')
                ->relationship()
                ->addActionLabel('Tambah Anggota')
                ->distinct('user_id')
                ->label('Daftar Anggota Proposal')
                ->helperText('Tambahkan anggota tim proposal selain Anda sebagai ketua.')
                ->minItems(0)
                ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                    $data['peran'] = 'anggota';

                    if (!empty($data['user_id'])) {
                        $user = \App\Models\User::find($data['user_id']);
                        if ($user) {
                            $data['nama'] = $user->name;
                            $data['nim_nidn'] = $user->nim_nidn;
                            $data['prodi'] = $user->prodi;
                            $data['uraian_tugas'] = $data['uraian_tugas'] ?? null;
                            $data['bidang_fokus'] = $data['bidang_fokus'] ?? null;

                            $allRoles = $user->getRoleNames();
                            $validTypes = ['dosen', 'mahasiswa'];
                            $filteredTypes = $allRoles->filter(function ($role) use ($validTypes) {
                                return in_array($role, $validTypes);
                            });

                            $tipeString = $filteredTypes->implode(', ');
                            $data['tipe'] = $tipeString ?: null;
                        }
                    }
                    return $data;
                })
                ->schema([
                    Forms\Components\Grid::make(12)
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->label('Nama Anggota')
                                ->relationship(
                                    name: 'user',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) =>
                                    $query
                                        ->where('id', '!=', Auth::id())
                                        ->whereHas('roles', function ($q) {
                                            $q->whereIn('name', ['dosen', 'mahasiswa']);
                                        })
                                )
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(function (mixed $state, \Filament\Forms\Set $set) { // <-- FIX LINTER
                                    if ($state) {
                                        $user = \App\Models\User::find($state);
                                        if ($user) {
                                            $set('nim_nidn', $user->nim_nidn);
                                            $set('prodi', $user->prodi);

                                            $allRoles = $user->getRoleNames();
                                            $validTypes = ['dosen', 'mahasiswa'];
                                            $filteredTypes = $allRoles->filter(function ($role) use ($validTypes) {
                                                return in_array($role, $validTypes);
                                            });

                                            $tipeString = $filteredTypes->implode(', ');
                                            $set('tipe', $tipeString ?: null);
                                        }
                                    } else {
                                        $set('nim_nidn', null);
                                        $set('tipe', null);
                                        $set('prodi', null);
                                    }
                                })
                                ->required()
                                ->columnSpan(6) 
                                ->disableOptionWhen(function (mixed $value, ?string $state, \Filament\Forms\Get $get): bool { // <-- FIX LINTER
                                    $repeaterItems = $get('../../anggota');

                                    if (empty($repeaterItems)) {
                                        return false;
                                    }

                                    $selectedUserIds = array_filter(array_column($repeaterItems, 'user_id'));

                                    return in_array($value, $selectedUserIds) && $value !== $state;
                                }),
                            Forms\Components\TextInput::make('nim_nidn')
                                ->label(fn (\Filament\Forms\Get $get): string => match ($get('tipe')) {
                                    'dosen' => 'NIDN',
                                    'mahasiswa' => 'NIM',
                                    default => 'NIM / NIDN',
                                })
                                ->disabled()
                                ->required()
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('tipe')
                                ->label('Tipe Anggota')
                                ->disabled()
                                ->required()
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('peran')
                                ->label('Peran Dalam Tim')
                                ->default('anggota')
                                ->disabled()
                                ->required()
                                ->columnSpan(6), 
                            
                            Forms\Components\TextInput::make('prodi')
                                ->label('Program Studi')
                                ->disabled()
                                ->required()
                                ->columnSpan(6),
                            Forms\Components\TextArea::make('uraian_tugas')
                                ->label('Uraian Tugas Anggota')
                                ->rows(2)
                                ->placeholder('Contoh: Bertanggung jawab pada bagian survei lapangan...')
                                ->nullable()
                                ->visible(fn ($get) => in_array($get('../../kategori'), ['Pengabdian', 'Penelitian & Pengabdian']) )
                                ->columnSpanfull(),
                            Forms\Components\TextInput::make('bidang_fokus')
                                ->label('Bidang Fokus Anggota')
                                ->placeholder('Contoh: Teknologi Informasi, Kesehatan...')
                                ->nullable()
                                ->visible(function (\Filament\Forms\Get $get): bool {
                                    $Penelitian = in_array($get('../../kategori'), ['Penelitian', 'Penelitian & Pengabdian']);
                                    $Dosen = str_contains($get('tipe'), 'dosen');

                                    return $Penelitian && $Dosen;
                                })
                                ->columnSpanfull(), 
                            
                            Forms\Components\FileUpload::make('file_tambahan')
                                ->label('File Tambahan Anggota (PDF)')
                                ->disk('public')
                                ->directory('anggota-files')
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(2048)
                                ->required()
                                ->visible(fn (\Filament\Forms\Get $get): bool => $get('tipe') === 'dosen')
                                ->columnSpanFull(),
                        ]),
                ]),

            Forms\Components\Section::make('Luaran Wajib & Tambahan')
                ->description('Tambahkan daftar luaran yang akan dihasilkan dari proposal ini.')
                ->schema([
                    Forms\Components\Repeater::make('luaran')
                        ->relationship()
                        ->label('Luaran')
                        ->addActionLabel('Tambah Luaran')
                        ->minItems(0)
                        ->columns(2)
                        ->schema([
                            Forms\Components\Select::make('jenis_luaran')
                                ->label('Jenis Luaran')
                                ->options([
                                    'jurnal_nasional' => 'Jurnal Nasional Terakreditasi',
                                    'jurnal_internasional' => 'Jurnal Internasional Bereputasi',
                                    'buku_ajar' => 'Buku Ajar',
                                    'paten' => 'Paten',
                                    'hki' => 'HKI (Hak Kekayaan Intelektual)',
                                    'lainnya' => 'Lainnya',
                                ])
                                ->required()
                                ->columnSpan(1),
                            Forms\Components\Select::make('status')
                                ->label('Status Pengerjaan')
                                ->options([
                                    'belum_dimulai' => 'Belum Dimulai',
                                    'dalam_pengerjaan' => 'Dalam Pengerjaan',
                                    'selesai' => 'Selesai',
                                    'dibatalkan' => 'Dibatalkan',
                                ])
                                ->default('belum_dimulai')
                                ->required()
                                ->columnSpan(1),
                            Forms\Components\Textarea::make('tujuan')
                                ->label('Tujuan / Target Luaran')
                                ->rows(3)
                                ->required()
                                ->placeholder('Contoh: Publikasi di Jurnal SINTA 2...')
                                ->helperText('Jelaskan target akhir dari luaran ini.')
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('deskripsi')
                                ->label('Deskripsi / Keterangan Progres')
                                ->rows(3)
                                ->placeholder('Contoh: Masih 70% pengumpulan data...')
                                ->helperText('Jelaskan progres atau kondisi luaran ini sekarang.')
                                ->columnSpanFull(),
                            
                            Forms\Components\FileUpload::make('file')
                                ->label('Upload Draft/Bukti Luaran (PDF/DOCX)')
                                ->disk('public')
                                ->directory('luaran-files')
                                ->acceptedFileTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                ->maxSize(5120)
                                ->columnSpanFull(),
                        ])
                        ->defaultItems(0)
                        ->collapsible()
                ]),
        ]);
    }

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
                                            ])->fullWidth(),
                                        ]),
                                ]),
                        ])
                        ->columnSpan(2),

                    Grid::make(1)
                        ->schema([
                            Section::make('Catatan Proposal')
                                ->icon('heroicon-o-document-check')
                                ->schema([
                                    TextEntry::make('catatan')
                                        ->label('Catatan dari Reviewer')
                                        ->markdown()
                                        ->icon('heroicon-o-chat-bubble-left-right')
                                        ->color('warning')
                                        ->visible(fn (Proposal $record) => $record->status === 'revisi' && filled($record->catatan)),

                                    InfolistActions::make([
                                        InfolistAction::make('downloadFile')
                                            ->label('Download Proposal')
                                            ->icon('heroicon-o-arrow-down-tray')->color('gray')
                                            ->url(fn (Proposal $record): string => $record->file ? route('proposals.download', $record) : '')
                                            ->openUrlInNewTab()
                                            ->visible(fn (Proposal $record) => filled($record->file)),

                                        InfolistAction::make('gantiKetua')
                                            ->label('Ganti Ketua Proposal')
                                            ->visible(function (Proposal $record): bool {
                                                $isOwner = Auth::id() === $record->user_id;
                                                $isEditableStatus = in_array($record->status, ['menunggu', 'revisi']);
                                                $hasEligibleDosen = $record->anggota()->where('tipe', 'dosen')->exists();
                                                return $isOwner && $isEditableStatus && $hasEligibleDosen;
                                            })
                                            ->form([
                                                Select::make('new_ketua_user_id')
                                                    ->label('Pilih Ketua Baru')
                                                    ->options(function (Proposal $record) {
                                                        return $record->anggota()
                                                            ->where('tipe', 'dosen')
                                                            ->get()
                                                            ->pluck('nama', 'user_id');
                                                    })
                                                    ->required()->native(false)->searchable(),
                                            ])
                                            ->action(function (array $data, Proposal $record) {
                                                /** @var \App\Models\User $oldKetuaUser */
                                                $oldKetuaUser = Auth::user();
                                                $newKetuaUserId = $data['new_ketua_user_id'];
                                                try {
                                                    DB::transaction(function () use ($record, $oldKetuaUser, $newKetuaUserId) {
                                                        $anggotaBaruRecord = $record->anggota()->where('user_id', $newKetuaUserId)->first();
                                                        if (!$anggotaBaruRecord) {
                                                            throw new \Exception('Data anggota tidak ditemukan.');
                                                        }
                                                        $anggotaBaruRecord->delete();
                                                        $allRoles = $oldKetuaUser->getRoleNames();
                                                        $validTypes = ['dosen', 'mahasiswa'];
                                                        $filteredTypes = $allRoles->filter(fn ($role) => in_array($role, $validTypes));
                                                        $tipeString = $filteredTypes->implode(', ') ?: 'dosen';
                                                        $record->anggota()->create([
                                                            'user_id'   => $oldKetuaUser->id,
                                                            'nama'      => $oldKetuaUser->name,
                                                            'nim_nidn'  => $oldKetuaUser->nim_nidn,
                                                            'tipe'      => $tipeString,
                                                            'peran'     => 'anggota',
                                                        ]);
                                                        $record->update(['user_id' => $newKetuaUserId]);
                                                    });
                                                } catch (\Exception $e) {
                                                    Notification::make()
                                                        ->title('Gagal Mengganti Ketua')
                                                        ->body($e->getMessage())
                                                        ->danger()
                                                        ->send();
                                                    return;
                                                }
                                                Notification::make()
                                                    ->title('Ketua Proposal Berhasil Diganti')
                                                    ->success()
                                                    ->send();
                                            }),
                                        
                                        InfolistAction::make('keluarTim')
                                            ->label('Keluar dari Tim Proposal')
                                            ->visible(function (Proposal $record): bool {
                                                $isOwner = Auth::id() === $record->user_id;
                                                $isAnggota = $record->anggota()->where('user_id', Auth::id())->exists();
                                                return !$isOwner && $isAnggota;
                                            })
                                            ->action(function (Proposal $record) {
                                                try {
                                                    $anggotaRecord = $record->anggota()->where('user_id', Auth::id())->first();
                                                    if ($anggotaRecord) {
                                                        $anggotaRecord->delete();
                                                        Notification::make()->title('Berhasil Keluar Tim')->success()->send();
                                                    } else {
                                                        throw new \Exception('Data keanggotaan tidak ditemukan.');
                                                    }
                                                } catch (\Exception $e) {
                                                    Notification::make()->title('Gagal Keluar Tim')->body($e->getMessage())->danger()->send();
                                                }
                                            }),

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
                Tables\Columns\TextColumn::make('tahun_pelaksanaan')
                    ->label('Tahun')
                    ->sortable(),
                Tables\Columns\TextColumn::make('semester')
                    ->sortable()
                    ->label('Semester'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Ketua Proposal')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->description(function (Proposal $record){
                        $jumlahAnggota = $record->anggota_count ?? 0;

                        if ($jumlahAnggota > 0) {
                            return "Dengan {$jumlahAnggota} anggota";
                        }
                        return 'Tanpa anggota';
                    }),
                Tables\Columns\TextColumn::make('peran')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn (Model $record): string => ($record->user_id === Auth::id()) ? 'Ketua' : 'Anggota'),
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
                        if ($record->status === 'revisi') {
                            return 'Perlu perbaikan. Klik "View" untuk lihat catatan.';
                        }
                        return match ($record->status) {
                            'menunggu' => 'Proposal sedang direview.',
                            'diterima' => 'Selamat, proposal Anda disetujui!',
                            'ditolak'  => 'Proposal tidak dapat dilanjutkan.',
                            default    => '',
                        };
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['menunggu' => 'Menunggu', 'revisi' => 'Revisi', 'diterima' => 'Diterima', 'ditolak' => 'Ditolak']),
                Tables\Filters\SelectFilter::make('kategori')
                    ->options(['Penelitian' => 'Penelitian', 'Pengabdian' => 'Pengabdian']),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->infolist(static::infolist(...)),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProposals::route('/'),
            'create' => Pages\CreateProposal::route('/create'),
            'view' => Pages\ViewProposal::route('/{record}'),
            'edit' => Pages\EditProposal::route('/{record}/edit'),
        ];
    }
}
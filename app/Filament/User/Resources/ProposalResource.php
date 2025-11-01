<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\ProposalResource\Pages;
use App\Models\Proposal;
use App\Models\User;
// --- Import komponen yang dibutuhkan Infolist ---
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
// --- Import komponen lainnya ---
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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

        return parent::getEloquentQuery()
            ->with(['anggota.user'])
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('anggota', function (Builder $subQuery) use ($user) {
                        $subQuery->where('user_id', $user->id);
                    });
            });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Utama Proposal')
                ->description('Isi detail utama dari proposal penelitian atau pengabdian Anda.')
                ->schema([
                    Forms\Components\TextInput::make('judul')->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\Textarea::make('ringkasan')->required()->rows(4)->columnSpanFull(),
                    Forms\Components\Select::make('kategori')
                        ->label('Kategori Proposal')
                        ->options(['Penelitian' => 'Penelitian', 'Pengabdian' => 'Pengabdian'])
                        ->required()->reactive(),
                    Forms\Components\TextInput::make('tahun_pelaksanaan')->numeric()->label('Tahun Pelaksanaan')
                        ->minValue(2020)->maxValue(2200)->required(),
                    Forms\Components\TextInput::make('bidang_fokus')->label('Bidang Fokus')
                        ->nullable()->visible(fn ($get) => $get('kategori') === 'Penelitian'),
                    Forms\Components\Textarea::make('uraian_tugas')->label('Uraian Tugas')
                        ->rows(3)->nullable()->visible(fn ($get) => $get('kategori') === 'Pengabdian'),
                    Forms\Components\FileUpload::make('file')->label('File Proposal (PDF)')
                        ->disk('public')->directory('proposals')->acceptedFileTypes(['application/pdf'])
                        ->maxSize(10240)->required()->columnSpanFull(),
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

                    if(!empty($data['user_id'])) {
                        $user = \App\Models\User::find($data['user_id']);
                        if ($user) {
                            $data['nama'] = $user->name;
                            $data['nim_nidn'] = $user->nim_nidn;
                            $allRoles = $user->getRoleNames();
                            $validTypes = ['dosen', 'mahasiswa'];
                            $filteredTypes = $allRoles->filter(function ($role) use ($validTypes){
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
                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                            if ($state) {
                                $user = \App\Models\User::find($state);
                                if ($user) {
                                    $set('nim_nidn', $user->nim_nidn);
                                    
                                    $allRoles = $user->getRoleNames();
                                    $validTypes = ['dosen', 'mahasiswa'];
                                    $filteredTypes = $allRoles->filter(function ($role) use ($validTypes){
                                        return in_array($role, $validTypes);
                                    });
                                    $tipeString = $filteredTypes->implode(', ');
                                    $set('tipe', $tipeString ?: null);
                                }
                            } else {
                                $set('nim_nidn', null);
                                $set('tipe', null);
                            }
                        })
                        ->required()
                        ->columnSpan(6)
                        ->disableOptionWhen(function($value, ?string $state, \Filament\Forms\Get $get): bool{
                            $repeaterItems = $get('../../anggota');

                            if (empty($repeaterItems)) {
                                return false;
                            }

                            $selectedUserIds = array_filter(array_column($repeaterItems, 'user_id'));

                            return in_array($value, $selectedUserIds) && $value !== $state;
                        }),
                    Forms\Components\TextInput::make('nim_nidn')
                        ->label(fn (\Filament\Forms\Get $get): string => match($get('tipe')){
                            'dosen' => 'NIDN',
                            'mahasiswa' => 'NIM',
                            default => 'NIM / NIDN',
                        })
                        ->disabled()
                        ->required()
                        ->columnSpan(3),
                    Forms\Components\TextInput::make('tipe')
                        ->label('Tipe Anggota')
                        ->disabled()
                        ->required()
                        ->columnSpan(3),
                    Forms\Components\TextInput::make('peran')
                        ->label('Peran dalam Proposal')
                        ->default('anggota')
                        ->disabled()
                        ->required()
                        ->columnSpan(4),
                    Forms\Components\FileUpload::make('file_tambahan')
                        ->label('File Tambahan Anggota (PDF)')
                        ->disk('public')
                        ->directory('anggota-files')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(2048)
                        ->visible(fn (\Filament\Forms\Get $get): bool => $get('tipe') === 'dosen')
                        ->columnSpanFull(),
                    ]),
            ])
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
                                    TextEntry::make('judul')
                                        ->label('Judul Proposal')
                                        ->columnSpanFull(),
                                    TextEntry::make('status')
                                        ->label('Status Saat Ini')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'menunggu' => 'warning', 'diterima' => 'success',
                                            'revisi'   => 'info', 'ditolak'   => 'danger',
                                            default    => 'gray',
                                        }),
                                    TextEntry::make('kategori')->badge(),
                                    TextEntry::make('tahun_pelaksanaan')->label('Tahun'),
                                    TextEntry::make('ringkasan')
                                        ->columnSpanFull()
                                        ->markdown(),
                                ]),
                            
                            Section::make('Tim Proposal')
                                ->icon('heroicon-o-users')
                                ->schema([
                                    \Filament\Infolists\Components\ViewEntry::make('anggota')
                                        ->hiddenLabel()
                                        ->view('filament.infolists.components.tim-proposal-view')
                                        ->viewData([
                                            'record' => $infolist->getRecord(),
                                        ])
                                ]),
                        ])
                        ->columnSpan(2), 

                    Grid::make(1)
                        ->schema([
                            Section::make('Catatan & Tindakan')
                                ->icon('heroicon-o-document-check')
                                ->schema([
                                    TextEntry::make('catatan')
                                        ->label('Catatan dari Reviewer')
                                        ->markdown()
                                        ->icon('heroicon-o-chat-bubble-left-right')
                                        ->color('warning')
                                        ->visible(fn ($record) => $record->status === 'revisi' && filled($record->catatan)),
                                    
                                    InfolistActions::make([
                                        InfolistAction::make('downloadFile')
                                            ->label('Download Proposal')
                                            ->icon('heroicon-o-arrow-down-tray')->color('gray')
                                            ->url(fn (Proposal $record): string => $record->file ? route('proposals.download', $record) : '')
                                            ->openUrlInNewTab()
                                            ->visible(fn ($record) => filled($record->file)),
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
                ->description(fn ($record): string => "Kategori: {$record->kategori}"),
                Tables\Columns\TextColumn::make('tahun_pelaksanaan')->label('Tahun')->sortable(),
                Tables\Columns\TextColumn::make('peran')->badge()->color('info')
                ->getStateUsing(fn (Model $record): string => ($record->user_id === Auth::id()) ? 'Ketua' : 'Anggota'),
                Tables\Columns\TextColumn::make('status')->badge()->searchable()->sortable()
                ->color(fn (string $state): string => match ($state) {
                        'menunggu' => 'warning', 'diterima' => 'success',
                        'revisi' => 'info', 'ditolak' => 'danger',
                        default => 'gray',
                    })
                    ->description(function ($record) {
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
                    ->options(['menunggu' => 'Menunggu','revisi' => 'Revisi','diterima' => 'Diterima','ditolak' => 'Ditolak']),
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
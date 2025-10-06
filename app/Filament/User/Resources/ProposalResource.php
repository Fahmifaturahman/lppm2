<?php

namespace App\Filament\User\Resources;

use App\Models\Proposal;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Filament\User\Resources\ProposalResource\Pages;
use Filament\Tables;


class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $label = 'Proposal';
    protected static ?string $pluralLabel = 'Daftar Proposal';

    public static function canEdit(Model $record): bool
    {
        return Auth::id() === $record->user_id && $record->status === 'revisi';
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::id() === $record->user_id && $record->status === 'ditolak';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Mahasiswa hanya bisa melihat proposal miliknya
        if ($user->hasRole('mahasiswa')) {
            $query->where('user_id', $user->id);
        }
        // Dosen hanya bisa melihat proposal yang dia ajukan
        elseif ($user->hasRole('dosen')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('judul')
                ->required()
                ->maxLength(255),

            Forms\Components\Textarea::make('ringkasan')
                ->required()
                ->rows(4),

            Forms\Components\Select::make('kategori')
                ->label('Kategori Proposal')
                ->options([
                    'Penelitian' => 'Penelitian',
                    'Pengabdian' => 'Pengabdian',
                ])
                ->required()
                ->reactive(), // penting untuk trigger perubahan

            Forms\Components\Select::make('peran')
                ->options([
                    'ketua' => 'Ketua',
                    'anggota' => 'Anggota',
                ])
                ->required(),

            Forms\Components\TextInput::make('tahun_pelaksanaan')
                ->numeric()
                ->label('Tahun Pelaksanaan')
                ->minValue(2020)
                ->maxValue(2030)
                ->required(),

            Forms\Components\TextInput::make('bidang_fokus')
                ->label('Bidang Fokus')
                ->nullable()
                ->visible(fn ($get) => $get('kategori') === 'Penelitian'),

            Forms\Components\Textarea::make('uraian_tugas')
                ->label('Uraian Tugas (Ketua)')
                ->rows(3)
                ->nullable()
                ->visible(fn ($get) => $get('kategori') === 'Pengabdian'),

            Forms\Components\FileUpload::make('file')
                ->label('File Proposal (PDF)')
                ->disk('public')
                ->directory('proposals')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(10240)
                ->required(),

            Forms\Components\Repeater::make('anggota')
                ->label('Anggota Proposal')
                ->relationship() // pastikan relasi sudah dibuat di model Proposal
                ->schema([
                    Forms\Components\Select::make('tipe')
                        ->label('Tipe')
                        ->options([
                            'dosen' => 'Dosen',
                            'mahasiswa' => 'Mahasiswa',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('nim_nidn')
                        ->label('NIM / NIDN')
                        ->required(),

                    Forms\Components\TextInput::make('nama')
                        ->required(),

                    Forms\Components\Select::make('peran')
                        ->options([
                            'ketua' => 'Ketua',
                            'anggota' => 'Anggota',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('prodi')
                        ->label('Program Studi')
                        ->nullable(),

                    Forms\Components\TextInput::make('rumpun_ilmu_lv2')
                        ->label('Rumpun Ilmu Level 2')
                        ->nullable()
                        ->visible(fn ($get,) =>
                            data_get($get('tipe'), '') === 'dosen' &&
                            data_get($get('../../kategori'), '') === 'Pengabdian'
                        ),

                    Forms\Components\Textarea::make('uraian_tugas')
                        ->label('Uraian Tugas')
                        ->rows(2)
                        ->nullable()
                        ->visible(fn ($get,) =>
                            data_get($get('../../kategori'), '') === 'Pengabdian'
                        ),
                ])
                ->defaultItems(1)
                ->minItems(1)
                ->columns(2)
                ->addActionLabel('Tambah Anggota')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('judul')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('ringkasan')->limit(60),
                Tables\Columns\TextColumn::make('kategori')->label('Kategori')->badge(),
                Tables\Columns\TextColumn::make('tahun_pelaksanaan')->label('Tahun'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'menunggu' => 'warning',
                    'diterima' => 'success',
                    'revisi' => 'info',
                    'ditolak' => 'danger',
                    default => 'gray',
                }),
                Tables\Columns\TextColumn::make('catatan')->label('Catatan Reviewer')->limit(50),
                Tables\Columns\TextColumn::make('view_link') 
                    ->label('View')
                    ->url(fn ($record) => $record && $record->file ? asset('storage/' . $record->file) : null)
                    ->getStateUsing(fn () => '📄 Lihat')
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record && filled($record->file)),

                Tables\Columns\TextColumn::make('download_link')
                    ->label('Download')
                    ->getStateUsing(fn () => '📥 Download')
                    ->url(fn ($record) => route('download-proposal', ['file' => basename($record->file)]))
                    ->visible(fn ($record) => $record && filled($record->file)),


            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()    
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProposals::route('/'),
            'create' => Pages\CreateProposal::route('/create'),
            'view' => Pages\ViewProposal::route('/{record}'),
        ];
    }
}

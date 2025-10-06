<?php

namespace App\Filament\Admin\Resources;

use App\Models\Proposal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Admin\Resources\ProposalResource\Pages;

class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $label = 'Proposal';
    protected static ?string $pluralLabel = 'Proposal';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('judul')
                ->disabled()
                ->maxLength(255),

            Forms\Components\Textarea::make('ringkasan')
                ->disabled()
                ->rows(4),

            Forms\Components\Select::make('kategori')
                ->label('Kategori Proposal')
                ->disabled()
                ->options([
                    'Penelitian' => 'Penelitian',
                    'Pengabdian' => 'Pengabdian',
                ]),

            Forms\Components\TextInput::make('tahun_pelaksanaan')
                ->label('Tahun Pelaksanaan')
                ->disabled(),

            Forms\Components\TextInput::make('bidang_fokus')
                ->label('Bidang Fokus')
                ->disabled()
                ->visible(fn ($get) => $get('kategori') === 'Penelitian'),

            Forms\Components\Textarea::make('uraian_tugas')
                ->label('Uraian Tugas (Ketua)')
                ->rows(3)
                ->disabled()
                ->visible(fn ($get) => $get('kategori') === 'Pengabdian'),

            Forms\Components\FileUpload::make('file')
                ->label('File Proposal (PDF)')
                ->disk('public')
                ->directory('proposals')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(10240)
                ->disabled()
                ->visible(fn ($record) => filled($record?->file)),

            Forms\Components\Repeater::make('anggota')
                ->label('Anggota Proposal')
                ->relationship()
                ->disabled()
                ->schema([
                    Forms\Components\TextInput::make('nama')->disabled(),
                    Forms\Components\TextInput::make('nim_nidn')->label('NIM / NIDN')->disabled(),
                    Forms\Components\Select::make('tipe')->label('Tipe')->options([
                        'dosen' => 'Dosen',
                        'mahasiswa' => 'Mahasiswa',
                    ])->disabled(),
                    Forms\Components\Select::make('peran')->options([
                        'ketua' => 'Ketua',
                        'anggota' => 'Anggota',
                    ])->disabled(),
                    Forms\Components\TextInput::make('prodi')->label('Program Studi')->disabled(),
                    Forms\Components\TextInput::make('rumpun_ilmu_lv2')
                        ->label('Rumpun Ilmu Level 2')
                        ->disabled()
                        ->visible(fn ($get,) =>
                            data_get($get('tipe'), '') === 'dosen' &&
                            data_get($get('../../kategori'), '') === 'Pengabdian'
                        ),
                    Forms\Components\Textarea::make('uraian_tugas')
                        ->label('Uraian Tugas')
                        ->rows(2)
                        ->disabled()
                        ->visible(fn ($get,) =>
                            data_get($get('../../kategori'), '') === 'Pengabdian'
                        ),
                ])
                ->columns(2)
                ->minItems(1)
                ->disableItemCreation()
                ->disableItemDeletion(),

            Forms\Components\Select::make('status')
                ->label('Status Proposal')
                ->options([
                    'menunggu' => 'Menunggu',
                    'diterima' => 'Diterima',
                    'revisi' => 'Revisi',
                    'ditolak' => 'Ditolak',
                ])
                ->required(),

            Forms\Components\Textarea::make('catatan')
                ->label('Catatan Reviewer')
                ->rows(3)
                ->nullable(),
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
                Tables\Columns\TextColumn::make('download_link')
                    ->label('Download')
                    ->getStateUsing(fn () => '📥 Download')
                    ->url(fn ($record) => route('download-proposal', ['file' => basename($record->file)]))
                    ->visible(fn ($record) => filled(optional($record)->file)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'menunggu' => 'Menunggu',
                    'diterima' => 'Diterima',
                    'revisi' => 'Revisi',
                    'ditolak' => 'Ditolak',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProposals::route('/'),
            'view' => Pages\ViewProposal::route('/{record}'),
            'edit' => Pages\EditProposal::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProposalResource\Pages;
use App\Models\Proposal;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $label = 'Manajemen Proposal';
    protected static ?string $pluralLabel = 'Manajemen Proposal';
    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
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
                Tables\Columns\TextColumn::make('judul')->searchable()->limit(40)->tooltip(fn ($record) => $record->judul),
                Tables\Columns\TextColumn::make('user.name')->label('Ketua Pengaju')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('kategori')->badge(),
                Tables\Columns\TextColumn::make('tahun_pelaksanaan')->label('Tahun')->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'menunggu' => 'warning', 'diterima' => 'success',
                    'revisi' => 'info', 'ditolak' => 'danger',
                    default => 'gray',
                })->searchable()->sortable(),
            ])
            ->filters([
                 Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'diterima' => 'Diterima',
                        'revisi' => 'Revisi',
                        'ditolak' => 'Ditolak',
                    ]),
                Tables\Filters\SelectFilter::make('kategori')
                    ->options([
                        'Penelitian' => 'Penelitian',
                        'Pengabdian' => 'Pengabdian',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('diterima')
                        ->label('Diterima')
                        ->icon('heroicon-o-check-circle')->color('success')->requiresConfirmation()
                        ->action(fn (Proposal $record) => $record->update(['status' => 'diterima']))
                        ->visible(fn (Proposal $record): bool => in_array($record->status, ['menunggu', 'revisi'])),
                    Tables\Actions\Action::make('revisi')
                        ->label('Minta Revisi')
                        ->icon('heroicon-o-arrow-path')->color('info')
                        ->form([
                            Forms\Components\Textarea::make('catatan')
                                ->label('Catatan Revisi')->helperText('Catatan ini akan dapat dilihat oleh pengaju proposal.')->required(),
                        ])
                        ->action(function (Proposal $record, array $data) {
                            $record->update(['status' => 'revisi', 'catatan' => $data['catatan']]);
                        })
                        ->visible(fn (Proposal $record): bool => $record->status === 'menunggu'),
                    Tables\Actions\Action::make('ditolak')
                        ->label('Ditolak')
                        ->icon('heroicon-o-x-circle')->color('danger')->requiresConfirmation()
                        ->action(fn (Proposal $record) => $record->update(['status' => 'ditolak']))
                        ->visible(fn (Proposal $record): bool => in_array($record->status, ['menunggu', 'revisi'])),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
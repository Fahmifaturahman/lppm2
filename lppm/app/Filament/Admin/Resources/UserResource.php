<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Enums\Tab;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Filament\Admin\Resources\UserResource\Pages;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $label = 'Akun';
    protected static ?string $pluralLabel = 'Manajemen Akun';

        public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['mahasiswa', 'dosen']); 
    }


    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('nim_nidn')
                ->label('NIM / NIDN')
                ->maxLength(50)
                ->required(fn (string $context) => $context === 'create')
                ->unique(ignoreRecord: true)
                ->helperText('Isi dengan NIM (untuk Mahasiswa) atau NIDN (untuk Dosen)')
                ->dehydrateStateUsing(fn ($state) => $state ? trim($state) : null)
                ->dehydrated(fn ($state) => filled($state)),

            TextInput::make('name')
                ->required(fn (string $context) => $context === 'create')
                ->maxLength(255)
                ->dehydrateStateUsing(fn ($state) => $state ? trim($state) : null)
                ->dehydrated(fn ($state) => filled($state)),
            

            TextInput::make('email')
                ->email()
                ->required(fn (string $context) => $context === 'create')
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn ($state) => $state ? trim($state) : null)
                ->dehydrated(fn ($state) => filled($state)),

            Select::make('prodi')
                ->label('Program Studi')
                ->options([
                    'Teknik Informatika' => 'Teknik Informatika',
                    'Sistem Informasi' => 'Sistem Informasi',
                ])
                ->required(fn (string $context) => $context === 'create') 
                ->dehydrateStateUsing(fn ($state) => $state ? trim($state) : null)
                ->dehydrated(fn ($state) => filled($state)),

            TextInput::make('password')
                ->password()
                ->label('Password')
                ->revealable()
                ->maxLength(255)
                ->required(fn (string $context) => $context === 'create') 
                ->dehydrateStateUsing(fn ($state) => $state ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state)),

            Select::make('roles')
                ->label('Peran')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->required(fn (string $context) => $context === 'create')
                ->dehydrated(fn ($state) => filled($state)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),

                Tables\Columns\TextColumn::make('pemilik')
                    ->label('Pemilik Akun')
                    ->getStateUsing(function ($record) {
                        if ($record->mahasiswa) {
                            return 'Mahasiswa';
                        }

                        if ($record->dosen) {
                            return $record->hasRole('admin')
                                ? 'Dosen (Admin)'
                                : 'Dosen';
                        }

                        if ($record->hasRole('admin')) {
                            return 'Admin (Non-Dosen)';
                        }

                        return 'Tidak Diketahui';
                    }),

                Tables\Columns\TextColumn::make('email')->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->colors([
                        'primary',
                        'success' => 'admin',
                        'info' => 'user',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe_akun')
                    ->label('Filter Tipe Akun')
                    ->options([
                        'mahasiswa' => 'Mahasiswa',
                        'dosen' => 'Dosen',
                        'admin' => 'Admin',
                        'non-terdeteksi' => 'Tidak Diketahui',
                    ])
                    ->query(function ($query, $state) {
                        if ($state === 'mahasiswa') {
                            return $query->whereHas('mahasiswa');
                        } elseif ($state === 'dosen') {
                            return $query->whereHas('dosen')
                                ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))
                                ->whereDoesntHave('mahasiswa');
                        } elseif ($state === 'admin') {
                            return $query->whereHas('roles', fn ($q) => $q->where('name', 'admin'));
                        } elseif ($state === 'non-terdeteksi') {
                            return $query->whereDoesntHave('mahasiswa')
                                ->whereDoesntHave('dosen')
                                ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'));
                        }

                        return $query; 
                    }),

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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

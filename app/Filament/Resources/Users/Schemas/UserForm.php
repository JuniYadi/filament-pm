<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Section::make()
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        \Filament\Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        \Filament\Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),

                        \Filament\Forms\Components\Select::make('roles')
                            ->relationship(
                                name: 'roles',
                                titleAttribute: 'name',
                            )
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->options(Role::query()->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}

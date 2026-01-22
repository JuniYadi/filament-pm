<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $title = 'Team Members';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Select::make('role')
                    ->options([
                        'Product Manager' => 'Product Manager',
                        'Developer' => 'Developer',
                        'Viewer' => 'Viewer',
                    ])
                    ->default('Developer')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('pivot.role')
                    ->label('Role')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('pivot.created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->recordSelectOptionsQuery(fn ($query) => $query->whereNotIn('id', $this->ownerRecord->members->pluck('id'))),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Tasks';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\Textarea::make('description')
                    ->rows(3),

                \Filament\Forms\Components\Select::make('assigned_to')
                    ->label('Assigned To')
                    ->options(User::query()->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'Backlog' => 'Backlog',
                        'To Do' => 'To Do',
                        'In Progress' => 'In Progress',
                        'Review' => 'Review',
                        'Done' => 'Done',
                    ])
                    ->default('Backlog')
                    ->required(),

                \Filament\Forms\Components\TextInput::make('order')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Backlog' => 'gray',
                        'To Do' => 'warning',
                        'In Progress' => 'info',
                        'Review' => 'primary',
                        'Done' => 'success',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Backlog' => 'Backlog',
                        'To Do' => 'To Do',
                        'In Progress' => 'In Progress',
                        'Review' => 'Review',
                        'Done' => 'Done',
                    ]),

                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigned To')
                    ->relationship('assignedTo', 'name'),
            ])
            ->recordActions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->defaultSort('order', 'asc');
    }

    public static function canViewForRecord(Model $ownerRecord): bool
    {
        return true;
    }
}

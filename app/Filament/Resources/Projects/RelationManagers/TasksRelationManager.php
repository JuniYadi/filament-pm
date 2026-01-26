<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\TaskStatus;
use App\Models\User;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Tasks';

    protected static ?string $recordTitleAttribute = 'title';

    protected function getCreatedByUserId(): int
    {
        return (int) auth()->id();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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

                DatePicker::make('due_date')
                    ->label('Due Date')
                    ->nullable(),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['project_id'] = $this->ownerRecord->id;
        $data['created_by'] = $this->getCreatedByUserId();

        return $data;
    }

    protected function getCreateFormAction(): CreateAction
    {
        return CreateAction::configure()
            ->mutateFormDataUsing(function (array $data): array {
                $data['project_id'] = $this->ownerRecord->id;
                $data['created_by'] = $this->getCreatedByUserId();

                return $data;
            });
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
                        'backlog' => 'gray',
                        'todo' => 'warning',
                        'in_progress' => 'info',
                        'review' => 'primary',
                        'done' => 'success',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && (($record->due_date instanceof \Carbon\Carbon ? $record->due_date : \Carbon\Carbon::parse($record->due_date))->isPast()) && $record->status !== 'done' ? 'danger' : null)
                    ->icon(fn ($record) => $record->due_date && (($record->due_date instanceof \Carbon\Carbon ? $record->due_date : \Carbon\Carbon::parse($record->due_date))->isPast()) && $record->status !== 'done' ? 'heroicon-o-exclamation-triangle' : null)
                    ->toggleable(),
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
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Users'),

                TernaryFilter::make('has_due_date')
                    ->label('Has Due Date')
                    ->placeholder('All Tasks')
                    ->trueLabel('With Due Date')
                    ->falseLabel('Without Due Date')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('due_date'),
                        false: fn (Builder $query) => $query->whereNull('due_date'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                BulkActionGroup::make([
                    BulkAction::make('changeStatus')
                        ->label('Change Status')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->form([
                            Select::make('status')
                                ->label('New Status')
                                ->options(collect(TaskStatus::cases())->pluck('value', 'value'))
                                ->required()
                                ->default('todo'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function (\App\Models\Task $task) use ($data) {
                                $task->update(['status' => $data['status']]);
                            });
                        })
                        ->successNotificationTitle('Status updated successfully')
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('reassignTasks')
                        ->label('Reassign Tasks')
                        ->icon('heroicon-o-user-plus')
                        ->requiresConfirmation()
                        ->form([
                            Select::make('assigned_to')
                                ->label('Assign To')
                                ->relationship(name: 'assignedTo', titleAttribute: 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $records->each(function (\App\Models\Task $task) use ($data) {
                                $task->update(['assigned_to' => $data['assigned_to']]);
                            });
                        })
                        ->successNotificationTitle('Tasks reassigned successfully')
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make()
                        ->successNotificationTitle('Tasks deleted successfully'),
                ]),
            ])
            ->toolbarActions([
                CreateAction::make(),
            ])
            ->defaultSort('order', 'asc');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}

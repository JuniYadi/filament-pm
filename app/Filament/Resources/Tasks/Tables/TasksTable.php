<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Enums\TaskStatus;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\SpatieTagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(50),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'danger' => 'todo',
                        'warning' => 'in_progress',
                        'info' => 'review',
                        'success' => 'done',
                    ]),

                TextColumn::make('priority')
                    ->badge()
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'medium',
                        'orange' => 'high',
                        'danger' => 'critical',
                    ]),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && (($record->due_date instanceof \Carbon\Carbon ? $record->due_date : \Carbon\Carbon::parse($record->due_date))->isPast()) && $record->status !== 'done' ? 'danger' : null)
                    ->icon(fn ($record) => $record->due_date && (($record->due_date instanceof \Carbon\Carbon ? $record->due_date : \Carbon\Carbon::parse($record->due_date))->isPast()) && $record->status !== 'done' ? 'heroicon-o-exclamation-triangle' : null),

                SpatieTagsColumn::make('tags')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'todo' => 'Todo',
                        'in_progress' => 'In Progress',
                        'review' => 'Review',
                        'done' => 'Done',
                    ]),

                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'critical' => 'Critical',
                    ]),

                SelectFilter::make('project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Project')
                    ->placeholder('All Projects'),

                SelectFilter::make('assigned_to')
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Assigned To')
                    ->placeholder('All Users'),

                SelectFilter::make('created_by')
                    ->relationship('createdBy', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Creator')
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

                // Note: Tags can be searched using the global search on the tags column
                // The Spatie Tags plugin doesn't provide a Filter component in v4
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
            ]);
    }
}

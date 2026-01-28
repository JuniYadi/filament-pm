<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BlockedByTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'blockedByTasks';

    protected static ?string $title = 'Blocked By (Must Complete First)';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'danger' => 'todo',
                        'warning' => 'in_progress',
                        'info' => 'review',
                        'success' => 'done',
                    ]),

                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'medium',
                        'orange' => 'high',
                        'danger' => 'critical',
                    ])
                    ->toggleable(),

                Tables\Columns\IconColumn::make('status')
                    ->label('Blocking?')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->getStateUsing(fn ($record) => $record->status !== 'done')
                    ->tooltip(fn ($record) => $record->status !== 'done' ? 'This task must be completed first' : 'Completed - no longer blocking'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DetachAction::make()
                    ->label('Remove Dependency'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Add Blocking Task')
                    ->form([
                        Select::make('recordId')
                            ->label('Task')
                            ->options(function () {
                                $currentTask = $this->ownerRecord;
                                return \App\Models\Task::query()
                                    ->where('id', '!=', $currentTask->id)
                                    ->whereNotIn('id', function ($query) use ($currentTask) {
                                        $query->select('blocked_task_id')
                                            ->from('task_dependencies')
                                            ->where('blocking_task_id', $currentTask->id);
                                    })
                                    ->with(['assignedTo', 'project'])
                                    ->get()
                                    ->pluck('title_with_project', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->hint('Tasks that must complete before this task can start'),
                    ])
                    ->action(function (AttachAction $action, array $data) {
                        $currentTask = $this->ownerRecord;
                        $blockingTask = \App\Models\Task::find($data['recordId']);

                        if (!$blockingTask) {
                            return;
                        }

                        // Check for circular dependency using the model validation
                        $dependency = new \App\Models\TaskDependency();
                        $dependency->blocking_task_id = $blockingTask->id;
                        $dependency->blocked_task_id = $currentTask->id;

                        try {
                            $dependency->save();
                            $action->successNotificationTitle('Blocking task added successfully');
                        } catch (\InvalidArgumentException $e) {
                            $action->failureNotificationTitle($e->getMessage());
                            $action->failure();
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}

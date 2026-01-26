<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\ActivityLog;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Task Details')
                    ->schema([
                        TextEntry::make('title'),
                        TextEntry::make('description')
                            ->markdown()
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'todo' => 'gray',
                                'in_progress' => 'warning',
                                'review' => 'info',
                                'done' => 'success',
                                default => 'gray',
                            }),

                        TextEntry::make('priority')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'low' => 'gray',
                                'medium' => 'warning',
                                'high' => 'orange',
                                'critical' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('project.name'),
                        TextEntry::make('assignedTo.name')
                            ->label('Assigned To'),
                        TextEntry::make('createdBy.name')
                            ->label('Created By'),
                    ])
                    ->columns(2),

                Section::make('Activity History')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('activityLogs')
                            ->hiddenLabel()
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        IconEntry::make('action')
                                            ->icon(fn (string $state): string => match ($state) {
                                                'created' => 'heroicon-o-plus-circle',
                                                'status_changed' => 'heroicon-o-arrow-path',
                                                'assigned' => 'heroicon-o-user',
                                                'title_updated' => 'heroicon-o-pencil',
                                                'description_updated' => 'heroicon-o-pencil',
                                                'deleted' => 'heroicon-o-trash',
                                                default => 'heroicon-o-information-circle',
                                            })
                                            ->color(fn (string $state): string => match ($state) {
                                                'created' => 'success',
                                                'status_changed' => 'warning',
                                                'assigned' => 'info',
                                                'title_updated' => 'primary',
                                                'description_updated' => 'primary',
                                                'deleted' => 'danger',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('user.name')
                                            ->label('User')
                                            ->default('System'),
                                        TextEntry::make('description')
                                            ->label('Action')
                                            ->state(function (ActivityLog $record): string {
                                                return match ($record->action) {
                                                    'created' => 'Task created',
                                                    'status_changed' => sprintf(
                                                        'Status changed from %s to %s',
                                                        $record->old_values['status'] ?? '?',
                                                        $record->new_values['status'] ?? '?'
                                                    ),
                                                    'assigned' => sprintf(
                                                        'Assigned to %s',
                                                        $record->new_values['assigned_to'] ?? 'unassigned'
                                                    ),
                                                    'title_updated' => 'Title updated',
                                                    'description_updated' => 'Description updated',
                                                    'deleted' => 'Task deleted',
                                                    default => $record->action,
                                                };
                                            }),
                                        TextEntry::make('created_at')
                                            ->label('When')
                                            ->dateTime('M j, Y g:i A')
                                            ->since()
                                            ->tooltip(fn (TextEntry $entry): ?string => $entry->getState()),
                                    ]),
                            ])
                            ->hidden(fn () => ActivityLog::where('task_id', $this->record->id)->count() === 0),
                    ])
                    ->collapsible(),
            ]);
    }
}

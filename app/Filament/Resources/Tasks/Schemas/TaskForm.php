<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\MentionProvider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('project_id')
                            ->relationship('project', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('assigned_to')
                            ->relationship('assignedTo', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        RichEditor::make('description')
                            ->columns(10)
                            ->mentions([
                                MentionProvider::make('@')
                                    ->items([
                                        1 => 'Jane Doe',
                                        2 => 'John Smith',
                                    ]),
                                MentionProvider::make('#')
                                    ->items([
                                        'bug' => 'Bug',
                                        'feature' => 'Feature',
                                    ]),

                            ])
                            ->extraInputAttributes(['style' => 'min-height: 20rem; max-height: 50vh; overflow-y: auto;'])
                            ->columnSpanFull(),

                        Select::make('status')
                            ->options([
                                'todo' => 'Todo',
                                'in_progress' => 'In Progress',
                                'review' => 'Review',
                                'done' => 'Done',
                            ])
                            ->default('todo')
                            ->required(),

                        Select::make('priority')
                            ->options([
                                'low' => 'Low',
                                'medium' => 'Medium',
                                'high' => 'High',
                                'critical' => 'Critical',
                            ])
                            ->default('low')
                            ->required(),

                        SpatieTagsInput::make('tags')
                            ->columnSpanFull(),

                        TextInput::make('order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        DatePicker::make('due_date')
                            ->label('Due Date')
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}

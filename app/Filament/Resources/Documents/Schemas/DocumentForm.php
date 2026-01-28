<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\DocumentTemplate;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns([
                        'sm' => 1,
                        'lg' => 4,
                    ])
                    ->schema([

                        Section::make()
                            ->schema([
                                Select::make('template_id')
                                    ->label('Use Template')
                                    ->placeholder('Select a template...')
                                    ->options(function () {
                                        return DocumentTemplate::query()
                                            ->with('user')
                                            ->get()
                                            ->mapWithKeys(function ($template) {
                                                $prefix = $template->is_system ? '📋 ' : '👤 ';
                                                $user = $template->user?->name ?? 'System';
                                                $label = "{$prefix}{$template->name} ({$user})";
                                                return [$template->id => $label];
                                            });
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $template = DocumentTemplate::find($state);
                                            if ($template) {
                                                $set('content', $template->render());
                                                $currentTitle = $get('title');
                                                if (empty($currentTitle)) {
                                                    $set('title', $template->name);
                                                }
                                            }
                                        }
                                    })
                                    ->helperText('Templates help you get started with pre-defined content'),

                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug($state))),

                                Hidden::make('slug'),

                                Select::make('project_id')
                                    ->relationship('project', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),

                                RichEditor::make('content')
                                    ->required()
                                    ->columnSpanFull(),

                                SpatieTagsInput::make('tags')
                                    ->columnSpanFull(),
                            ])->columnSpan(['lg' => 3]),

                        Section::make([
                            Toggle::make('is_published'),
                            Toggle::make('is_featured'),
                        ])->columnSpan(['lg' => 1]),

                    ]),

            ]);
    }
}

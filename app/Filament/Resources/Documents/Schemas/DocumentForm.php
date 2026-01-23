<?php
namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn($state, $set) => $set('slug', Str::slug($state))),

                                Hidden::make('slug'),

                                Select::make('project_id')
                                    ->relationship('project', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),

                                RichEditor::make('content')
                                    ->required()
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

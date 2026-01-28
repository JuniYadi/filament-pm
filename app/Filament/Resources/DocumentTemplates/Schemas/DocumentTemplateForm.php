<?php

namespace App\Filament\Resources\DocumentTemplates\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DocumentTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->hint('Auto-generated from name'),

                        Textarea::make('description')
                            ->rows(2)
                            ->maxLength(500)
                            ->hint('A brief description of when to use this template'),

                        Textarea::make('content')
                            ->required()
                            ->rows(15)
                            ->hint('Template content. Use placeholders like {{date}}, {{datetime}}, {{author}}'),

                        Toggle::make('is_system')
                            ->label('System Template')
                            ->helperText('System templates are available to all users')
                            ->default(false),
                    ]),
            ]);
    }
}

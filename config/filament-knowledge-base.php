<?php

// config for Guava/KnowledgeBasePanel
return [
    'flatfile-model' => \Guava\FilamentKnowledgeBase\Models\FlatfileNode::class,

    'cache' => [
        'prefix' => env('FILAMENT_KB_CACHE_PREFIX', 'filament_kb_'),
        'ttl' => env('FILAMENT_KB_CACHE_TTL', 86400),
    ],

    'icons' => [
        // Icons configuration - commented out due to package compatibility
        // 'documentation' => 'heroicon-o-document',
        // 'link' => 'heroicon-o-link',
        // 'group' => null,
    ],
];

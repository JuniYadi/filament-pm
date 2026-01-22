<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content'); // Markdown content
            $table->json('embedding')->nullable(); // Vector embedding for AI search
            $table->timestamps();

            $table->index('embedding', 'documents_embedding_index'); // For vector search
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

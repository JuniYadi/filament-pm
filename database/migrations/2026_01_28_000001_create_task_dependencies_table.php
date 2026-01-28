<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocking_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('blocked_task_id')->constrained('tasks')->cascadeOnDelete();
            $table->timestamps();

            // Prevent duplicate dependencies and self-dependencies
            $table->unique(['blocking_task_id', 'blocked_task_id'], 'unique_task_dependency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};

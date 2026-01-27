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
        // Add indexes to project_members for better performance
        Schema::table('project_members', function (Blueprint $table) {
            // Index for finding user's projects
            $table->index('user_id', 'idx_project_members_user_id');
            // Index for loading project members
            $table->index('project_id', 'idx_project_members_project_id');
        });

        // Add composite index to users for search performance
        Schema::table('users', function (Blueprint $table) {
            // Composite index for name/email search in dropdowns
            $table->index(['name', 'email'], 'idx_users_name_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            $table->dropIndex('idx_project_members_user_id');
            $table->dropIndex('idx_project_members_project_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_name_email');
        });
    }
};

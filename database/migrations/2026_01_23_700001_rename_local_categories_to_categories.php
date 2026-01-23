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
        // Drop foreign keys first
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });
        
        Schema::table('local_categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            if (Schema::hasColumn('local_categories', 'sync_id')) {
                $table->dropForeign(['sync_id']);
            }
        });

        // Rename the table
        Schema::rename('local_categories', 'categories');

        // Recreate foreign keys on the new table name
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');
                
            if (Schema::hasColumn('categories', 'sync_id')) {
                $table->foreign('sync_id')
                    ->references('id')
                    ->on('synchronizations')
                    ->onDelete('set null');
            }
        });
        
        Schema::table('items', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });
        
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            if (Schema::hasColumn('categories', 'sync_id')) {
                $table->dropForeign(['sync_id']);
            }
        });

        // Rename back
        Schema::rename('categories', 'local_categories');

        // Recreate foreign keys
        Schema::table('local_categories', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('local_categories')
                ->onDelete('set null');
                
            if (Schema::hasColumn('local_categories', 'sync_id')) {
                $table->foreign('sync_id')
                    ->references('id')
                    ->on('synchronizations')
                    ->onDelete('set null');
            }
        });
        
        Schema::table('items', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('id')
                ->on('local_categories')
                ->onDelete('set null');
        });
    }
};

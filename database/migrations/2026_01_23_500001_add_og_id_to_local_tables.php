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
        // Ajouter og_id à la table items
        Schema::table('items', function (Blueprint $table) {
            $table->string('og_id')->nullable()->unique()->after('sync_id');
            $table->index('og_id');
        });

        // Ajouter og_id à la table local_categories
        Schema::table('local_categories', function (Blueprint $table) {
            $table->string('og_id')->nullable()->unique()->after('sync_id');
            $table->index('og_id');
        });

        // Ajouter og_id à la table brands
        Schema::table('brands', function (Blueprint $table) {
            $table->string('og_id')->nullable()->unique()->after('sync_id');
            $table->index('og_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['og_id']);
            $table->dropColumn('og_id');
        });

        Schema::table('local_categories', function (Blueprint $table) {
            $table->dropIndex(['og_id']);
            $table->dropColumn('og_id');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex(['og_id']);
            $table->dropColumn('og_id');
        });
    }
};

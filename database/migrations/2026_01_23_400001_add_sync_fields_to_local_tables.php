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
        // Ajouter les champs de synchronisation à la table items
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('sync_id')->nullable()->after('id');
            $table->string('code')->nullable()->after('name');
            $table->integer('stock')->default(0)->after('sale_price');
            $table->string('original_image')->nullable()->after('image');
            
            $table->foreign('sync_id')
                ->references('id')
                ->on('synchronizations')
                ->onDelete('set null');
        });

        // Ajouter les champs de synchronisation à la table local_categories
        Schema::table('local_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('sync_id')->nullable()->after('id');
            $table->string('original_logo')->nullable()->after('logo');
            
            $table->foreign('sync_id')
                ->references('id')
                ->on('synchronizations')
                ->onDelete('set null');
        });

        // Ajouter les champs de synchronisation à la table brands
        Schema::table('brands', function (Blueprint $table) {
            $table->unsignedBigInteger('sync_id')->nullable()->after('id');
            $table->string('original_logo')->nullable()->after('logo');
            
            $table->foreign('sync_id')
                ->references('id')
                ->on('synchronizations')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['sync_id']);
            $table->dropColumn(['sync_id', 'code', 'stock', 'original_image']);
        });

        Schema::table('local_categories', function (Blueprint $table) {
            $table->dropForeign(['sync_id']);
            $table->dropColumn(['sync_id', 'original_logo']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropForeign(['sync_id']);
            $table->dropColumn(['sync_id', 'original_logo']);
        });
    }
};

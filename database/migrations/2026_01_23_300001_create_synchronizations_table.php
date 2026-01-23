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
        Schema::create('synchronizations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');           // item, category, brand
            $table->string('type_id');        // ID depuis l'API HomeIP
            $table->json('data');             // Données brutes de l'API
            $table->string('status')->default('unsync'); // unsync, synced, changed, error
            $table->timestamps();             // created_at, updated_at
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_updated_at')->nullable(); // Updated_at de l'API externe
            
            // Index unique pour éviter les doublons
            $table->unique(['type', 'type_id']);
            
            // Index pour les requêtes fréquentes
            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('synchronizations');
    }
};

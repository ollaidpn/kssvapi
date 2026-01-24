<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     * Table des zones de livraison avec hiérarchie parent/enfant
     * - Zones parentes (ex: Dakar, Thiès) = parent_id = null, price = null
     * - Sous-zones (ex: Guédiawaye, Plateau) = parent_id = zone_id, price = montant livraison
     */
    public function up(): void
    {
        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('price')->nullable(); // Prix en FCFA (null pour les zones parentes)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('delivery_zones')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};

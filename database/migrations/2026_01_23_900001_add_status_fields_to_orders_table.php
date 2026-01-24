<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Statut de livraison: pending, processing, delivered
            $table->string('delivery_status')->default('pending')->after('status');
            
            // Statut de paiement: pending, paid
            $table->string('payment_status')->default('pending')->after('delivery_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_status', 'payment_status']);
        });
    }
};

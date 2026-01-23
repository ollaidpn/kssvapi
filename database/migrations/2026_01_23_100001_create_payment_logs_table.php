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
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference')->unique();
            
            // Infos utilisateur
            $table->unsignedBigInteger('user_id');
            $table->json('user_info'); // {name, ccphone, phone, address, city}
            
            // Infos commande
            $table->json('items'); // [{name, image, qty, price, total}, ...]
            $table->unsignedBigInteger('promo_code_id')->nullable();
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            
            // Infos paiement
            $table->string('payment_method'); // wave_senegal, orange_money_senegal
            $table->string('transaction_id')->nullable();
            $table->string('payment_link')->nullable();
            $table->string('payment_qrcode')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Statut: pending, paid, failed, cancelled, expired
            $table->string('status')->default('pending');
            
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('promo_code_id')->references('id')->on('promo_codes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};

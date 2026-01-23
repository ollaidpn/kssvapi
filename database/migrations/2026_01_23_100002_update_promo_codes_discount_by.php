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
        Schema::table('promo_codes', function (Blueprint $table) {
            // Change discount_by from decimal to string (enum: 'amount', 'percent')
            $table->string('discount_by', 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo_codes', function (Blueprint $table) {
            $table->decimal('discount_by')->change();
        });
    }
};

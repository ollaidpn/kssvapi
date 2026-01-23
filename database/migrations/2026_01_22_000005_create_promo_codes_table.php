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
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('message')->nullable();
            $table->string('code');
            $table->decimal('discount_by');
            $table->decimal('discount_value');
            $table->string('filter_by');
            $table->bigInteger('limite')->nullable();
            $table->date('expiration')->nullable();
            $table
                ->string('status')
                ->default('active')
                ->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};

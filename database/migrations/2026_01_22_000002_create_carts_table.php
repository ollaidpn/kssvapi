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
        Schema::create('carts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->string('item_id');
            $table
                ->longText('item_infos')
                ->default('[]')
                ->nullable();
            $table->decimal('price');
            $table->bigInteger('qty');
            $table->decimal('total')->nullable();
            $table
                ->string('status')
                ->default('cart')
                ->nullable();
            $table->unsignedBigInteger('order_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};

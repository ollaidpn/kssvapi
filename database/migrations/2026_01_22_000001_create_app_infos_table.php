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
        Schema::create('app_infos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('ccphone1')->nullable();
            $table->string('phone1')->nullable();
            $table->string('ccphone2')->nullable();
            $table->string('phone2')->nullable();
            $table->string('email1')->nullable();
            $table->string('email2')->nullable();
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->string('logo_color')->nullable();
            $table->string('logo_white')->nullable();
            $table->string('address')->nullable();
            $table->string('town')->nullable();
            $table->string('country')->nullable();
            $table->boolean('maintenance')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_infos');
    }
};

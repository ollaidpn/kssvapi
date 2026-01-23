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
        Schema::table('app_infos', function (Blueprint $table) {
            $table->boolean('show_only_with_images')->default(false)->after('maintenance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_infos', function (Blueprint $table) {
            $table->dropColumn('show_only_with_images');
        });
    }
};

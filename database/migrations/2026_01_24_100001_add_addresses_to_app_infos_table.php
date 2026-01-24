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
            // Add JSON column for addresses
            $table->json('addresses')->nullable()->after('logo_white');
            
            // Drop old address columns
            $table->dropColumn(['address', 'town', 'country', 'latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_infos', function (Blueprint $table) {
            // Restore old columns
            $table->string('address')->nullable();
            $table->string('town')->nullable();
            $table->string('country')->nullable();
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            
            // Drop addresses JSON column
            $table->dropColumn('addresses');
        });
    }
};

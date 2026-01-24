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
        // Note: avatar field already exists in the original migration
        // This migration is for documentation purposes
        // If avatar doesn't exist, uncomment below:
        
        // Schema::table('users', function (Blueprint $table) {
        //     if (!Schema::hasColumn('users', 'avatar')) {
        //         $table->string('avatar')->nullable()->after('phone');
        //     }
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropColumn('avatar');
        // });
    }
};

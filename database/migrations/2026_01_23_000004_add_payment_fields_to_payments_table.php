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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('reference');
            $table->string('status')->nullable()->after('transaction_id');
            $table->text('payment_link')->nullable()->after('status');
            $table->timestamp('expires_at')->nullable()->after('payment_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['transaction_id', 'status', 'payment_link', 'expires_at']);
        });
    }
};

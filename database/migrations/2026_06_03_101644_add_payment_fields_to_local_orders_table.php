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
        Schema::table('local_orders', function (Blueprint $table) {
            $table->string('payment_method')->default('cod')->after('status');
            $table->string('payment_status')->default('pending')->after('payment_method');
            $table->string('stripe_session_id')->nullable()->after('payment_status');
            $table->string('stripe_payment_intent')->nullable()->after('stripe_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('local_orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_status', 'stripe_session_id', 'stripe_payment_intent']);
        });
    }
};

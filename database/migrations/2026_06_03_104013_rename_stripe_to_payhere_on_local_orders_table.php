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
            $table->renameColumn('stripe_session_id', 'payhere_order_id');
            $table->renameColumn('stripe_payment_intent', 'payhere_payment_id');
        });
    }

    public function down(): void
    {
        Schema::table('local_orders', function (Blueprint $table) {
            $table->renameColumn('payhere_order_id', 'stripe_session_id');
            $table->renameColumn('payhere_payment_id', 'stripe_payment_intent');
        });
    }
};

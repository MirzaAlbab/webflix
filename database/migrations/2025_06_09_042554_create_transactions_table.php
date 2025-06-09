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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('transaction_number')->unique(); // Unique identifier for the transaction
            $table->decimal('total_amount', 10, 2); // Amount in the smallest currency unit (e.g., cents)
            $table->enum('payment_status', ['pending', 'success', 'failed', 'expired']); // Status of the payment
            $table->string('midtrans_snap_token')->nullable(); // Token for Midtrans Snap API
            $table->string('midtrans_transaction_id')->nullable(); // Transaction ID from Midtrans
            $table->string('midtrans_booking_code')->nullable(); // Booking code from Midtrans
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

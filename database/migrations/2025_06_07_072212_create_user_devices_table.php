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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_name'); // e.g., 'iPhone 12', 'Samsung Galaxy S21'
            $table->string('device_id')->unique(); // Unique token for the device
            $table->string('device_type'); // e.g., 'mobile', 'tablet', 'desktop'
            $table->string('platform'); // e.g., 'iOS', 'Android', 'Web'
            $table->string('platform_version'); // e.g., '14.4', '11.0', 'Windows 10'
            $table->string('browser')->nullable(); // Browser name if applicable, e.g., 'Chrome', 'Safari'
            $table->string('browser_version')->nullable(); // Browser version if applicable, e.g., '89.0.4389.82'
            $table->timestamp('last_active')->nullable(); // Last time the device was used

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};

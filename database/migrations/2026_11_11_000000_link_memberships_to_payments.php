<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dateTime('start_date')->change();
            $table->dateTime('end_date')->change();
            $table->foreignId('transaction_id')->nullable()->unique()->constrained('transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->dropUnique(['transaction_id']);
            $table->dropConstrainedForeignId('transaction_id');
            $table->date('start_date')->change();
            $table->date('end_date')->change();
        });
    }
};

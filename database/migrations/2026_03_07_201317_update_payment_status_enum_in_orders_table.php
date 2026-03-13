<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update payment_status enum to include 'failed' and 'pending'
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('unpaid', 'paid', 'refunded', 'failed', 'pending') DEFAULT 'unpaid'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE orders MODIFY COLUMN payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid'");
    }
};

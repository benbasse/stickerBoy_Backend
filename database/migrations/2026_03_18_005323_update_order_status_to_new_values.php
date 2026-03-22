<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, change to string to allow any value temporarily
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(50) DEFAULT 'pending'");

        // Convert old status values to new ones
        DB::table('orders')->where('status', 'completed')->update(['status' => 'delivered']);
        DB::table('orders')->where('status', 'paid')->update(['status' => 'processing']);
        DB::table('orders')->where('status', 'unpaid')->update(['status' => 'pending']);
        DB::table('orders')->where('status', 'failed')->update(['status' => 'cancelled']);
        DB::table('orders')->where('status', 'refunded')->update(['status' => 'cancelled']);

        // Now set the new enum values
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'processing', 'shipping', 'delivered', 'cancelled') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old enum values
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'processing', 'completed', 'cancelled', 'refunded', 'paid', 'unpaid', 'failed') DEFAULT 'pending'");
    }
};

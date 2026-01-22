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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            //the product_id can be Sticker or totebag
            $table->foreignUuid('customer_id')->constrained('customers')->onDelete('cascade');
            $table->uuid('reference')->unique();
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled']);
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded']);
            $table->bigInteger('total_price');
            $table->string('payment_provider')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_link')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

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
        Schema::create('collection_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')->constrained('collections')->onDelete('cascade');
            // $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->uuid('product_id');
            $table->bigInteger('quantity');
            // $table->enum('product_type', ['sticker', 'totebags', 'other'])->default('other');
            // $table->index(['product_type', 'product_id']);
            $table->string('product_type');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_products');
    }
};

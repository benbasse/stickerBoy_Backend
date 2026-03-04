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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_number')->unique()->nullable()->after('id');
        });

        // Générer les numéros pour les commandes existantes
        $orders = \App\Models\Order::orderBy('id')->get();
        foreach ($orders as $index => $order) {
            $order->update([
                'order_number' => 'SB-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT)
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('order_number');
        });
    }
};

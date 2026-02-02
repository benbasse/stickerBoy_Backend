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
        Schema::create('ads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('cta_text');           // Texte du bouton (ex: "Shop Now")
            $table->string('cta_link');           // Lien du bouton (ex: "/collections")
            $table->enum('theme', ['warm', 'cool', 'nature', 'dark'])->default('warm');
            $table->enum('target', ['homepage', 'category', 'collection'])->default('homepage');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('image')->nullable();  // Image optionnelle (pour version avec image)
            $table->integer('order')->default(0); // Pour trier l'ordre d'affichage
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};

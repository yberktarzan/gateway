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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->json('title'); // Multi-language titles
            $table->json('description'); // Multi-language descriptions
            $table->string('cover_image')->nullable(); // Main listing image
            $table->json('images')->nullable(); // Gallery images array
            $table->json('slug'); // Multi-language slugs for SEO
            $table->string('location'); // Listing location
            $table->decimal('price', 12, 2)->nullable(); // Price (supports large amounts)
            $table->string('listing_type'); // From ListingType enum
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Owner
            $table->string('country_code', 2); // ISO country code
            $table->unsignedBigInteger('category_id')->nullable(); // For future category system
            $table->boolean('is_active')->default(true); // Active/inactive status
            $table->timestamps();

            // Indexes for better performance
            $table->index(['listing_type', 'is_active']);
            $table->index(['country_code', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->index(['category_id', 'is_active']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};

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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('country_code', 2); // ISO country code
            $table->string('name');
            $table->string('logo')->nullable();
            $table->json('description'); // Translatable descriptions
            $table->boolean('is_vip')->default(false); // VIP/featured companies
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->string('website')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['status', 'is_vip']);
            $table->index('country_code');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

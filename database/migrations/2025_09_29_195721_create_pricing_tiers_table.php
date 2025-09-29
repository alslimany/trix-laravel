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
        Schema::create('pricing_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('pricing_zones')->onDelete('cascade');
            $table->decimal('min_km', 8, 2); // Minimum distance in kilometers
            $table->decimal('max_km', 8, 2); // Maximum distance in kilometers
            $table->decimal('base_price', 8, 2); // Base price for this distance tier
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_tiers');
    }
};

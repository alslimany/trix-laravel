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
        Schema::create('vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('weight_min', 8, 2); // Minimum weight capacity in kg
            $table->decimal('weight_max', 8, 2); // Maximum weight capacity in kg
            $table->decimal('pricing_multiplier', 4, 2)->default(1.00); // Price multiplier for this vehicle type
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_types');
    }
};

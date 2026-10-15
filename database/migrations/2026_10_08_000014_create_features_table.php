<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('category');
            $table->timestamps();
        });

        Schema::create('vehicle_features', function (Blueprint $table): void {
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->primary(['vehicle_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_features');
        Schema::dropIfExists('features');
    }
};

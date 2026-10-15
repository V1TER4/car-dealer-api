<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->string('brand');
            $table->string('model');
            $table->string('version')->nullable();
            $table->unsignedSmallInteger('manufacture_year');
            $table->unsignedSmallInteger('model_year');
            $table->unsignedInteger('mileage')->default(0);
            $table->string('transmission');
            $table->string('fuel_type');
            $table->string('color')->nullable();
            $table->unsignedTinyInteger('doors')->nullable();
            $table->string('license_plate', 7)->nullable();
            $table->decimal('price', 12, 2);
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();
            $table->unique(['store_id', 'slug']);
            $table->index(['store_id', 'status', 'brand', 'model']);
            $table->index(['store_id', 'status', 'price']);
            $table->index(['store_id', 'status', 'model_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

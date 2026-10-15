<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = ['store_id', 'slug', 'brand', 'model', 'version', 'manufacture_year', 'model_year', 'mileage', 'transmission', 'fuel_type', 'color', 'doors', 'license_plate', 'price', 'description', 'status', 'published_at', 'sold_at'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'published_at' => 'datetime', 'sold_at' => 'datetime'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VehicleImage::class)->orderBy('sort_order');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'vehicle_features');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(VehicleStatusHistory::class)->latest();
    }
}

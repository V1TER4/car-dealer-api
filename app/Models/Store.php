<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'status', 'logo_path', 'favicon_path', 'primary_color', 'secondary_color', 'header_background', 'site_background', 'text_color', 'footer_background', 'footer_text_color', 'footer_accent_color', 'settings'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function domains(): HasMany
    {
        return $this->hasMany(StoreDomain::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot(['role', 'is_active'])->withTimestamps();
    }
}

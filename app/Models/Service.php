<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Service extends Model
{
    protected $fillable = ['service_category_id', 'name', 'description', 'price', 'duration_minutes', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function laserServiceSetting(): HasOne
    {
        return $this->hasOne(LaserServiceSetting::class);
    }
}

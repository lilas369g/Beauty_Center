<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaserServiceSetting extends Model
{
    protected $fillable = ['service_id'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function areas(): HasMany
    {
        return $this->hasMany(LaserServiceArea::class);
    }
}

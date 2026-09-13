<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LaserServiceArea extends Model
{
    protected $fillable = [
        'laser_service_setting_id',
        'laser_area_id',
        'default_price',
        'default_duration_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(LaserServiceSetting::class, 'laser_service_setting_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(LaserArea::class, 'laser_area_id');
    }

    public function appointmentServiceAreas(): HasMany
    {
        return $this->hasMany(AppointmentServiceLaserArea::class);
    }
}

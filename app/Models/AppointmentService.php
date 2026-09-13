<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentService extends Model
{
    protected $table = 'appointment_services';

    protected $fillable = ['appointment_id', 'service_id', 'booked_price', 'booked_duration_minutes', 'position'];

    protected function casts(): array
    {
        return ['booked_price' => 'decimal:2'];
    }

    public function laserAreas(): HasMany
    {
        return $this->hasMany(AppointmentServiceLaserArea::class);
    }
}

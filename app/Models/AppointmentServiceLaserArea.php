<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentServiceLaserArea extends Model
{
    protected $fillable = [
        'appointment_service_id',
        'laser_service_area_id',
        'performed_by_employee_id',
        'status',
        'area_name_snapshot',
        'price_snapshot',
        'duration_snapshot_minutes',
        'notes',
    ];

    protected function casts(): array
    {
        return ['price_snapshot' => 'decimal:2'];
    }

    public function appointmentService(): BelongsTo
    {
        return $this->belongsTo(AppointmentService::class);
    }

    public function laserServiceArea(): BelongsTo
    {
        return $this->belongsTo(LaserServiceArea::class);
    }

    public function performedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'performed_by_employee_id');
    }
}

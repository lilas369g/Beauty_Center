<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = ['full_name', 'email', 'phone', 'commission_rate', 'is_active'];

    protected function casts(): array
    {
        return ['commission_rate' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function performedLaserAreas(): HasMany
    {
        return $this->hasMany(AppointmentServiceLaserArea::class, 'performed_by_employee_id');
    }
}

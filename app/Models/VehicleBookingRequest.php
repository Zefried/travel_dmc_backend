<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleBookingRequest extends Model
{
    protected $fillable = [
        'vehicle_id',
        'main_admin_id',
        'vehicle_admin_id',
        'start_date',
        'end_date',
        'passenger_count',
        'requested_price',
        'note',
        'status',
        'rejection_note',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function mainAdmin()
    {
        return $this->belongsTo(User::class, 'main_admin_id');
    }

    public function vehicleAdmin()
    {
        return $this->belongsTo(User::class, 'vehicle_admin_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomBusySchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'hotel_admin_id',
        'start_date',
        'end_date',
        'reason',
        'note',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function hotelAdmin()
    {
        return $this->belongsTo(User::class, 'hotel_admin_id');
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRequestCreatedNotification extends Notification
{
    use Queueable;

    protected $bookingData;

    public function __construct($bookingData)
    {
        $this->bookingData = $bookingData;
    }

    public function via(object $notifiable): array
    {
        return ['database']; // Keeping it simple with database only
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'New booking request for ' . $this->bookingData['vehicle_name'],
            'vehicle_name' => $this->bookingData['vehicle_name'],
            'start_date' => $this->bookingData['start_date'],
            'end_date' => $this->bookingData['end_date'],
            'passenger_count' => $this->bookingData['passenger_count'],
            'link' => '/dashboard/booking-requests'
        ];
    }
}

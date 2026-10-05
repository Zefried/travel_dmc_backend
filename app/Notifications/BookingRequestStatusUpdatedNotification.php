<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRequestStatusUpdatedNotification extends Notification
{
    use Queueable;

    protected $bookingData;

    public function __construct($bookingData)
    {
        $this->bookingData = $bookingData;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $status = $this->bookingData['status'];
        $vehicleName = $this->bookingData['vehicle_name'];
        
        $message = $status === 'accepted' 
            ? "$vehicleName booking confirmed!" 
            : "$vehicleName booking rejected.";

        return [
            'message' => $message,
            'vehicle_name' => $vehicleName,
            'status' => $status,
            'rejection_note' => $this->bookingData['rejection_note'] ?? null,
            'link' => '/dashboard/vehicle-booking-status'
        ];
    }
}

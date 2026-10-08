<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InventoryAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $alertType;
    protected $message;
    protected $products;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $alertType, string $message, $products)
    {
        $this->alertType = $alertType;
        $this->message = $message;
        $this->products = $products;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database']; // Keeping only database for now until email/whatsapp is set
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->alertType,
            'message' => $this->message,
            'product_count' => count($this->products),
        ];
    }
}

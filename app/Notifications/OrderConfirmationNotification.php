<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Order;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public $order;
    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->order->load(['user','items.product']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Order Confirmation #{$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Thank you for your order! Here are the details:")
            ->line("Order Total: $" . number_format($this->order->total, 2));

        foreach ($this->order->items as $item) {
            $productName = $item->product->name ?? 'Unknown';
            $mail->line("• {$productName} (x{$item->quantity})");
        }

        return $mail->line("We’ll let you know once your order is shipped.")
            ->salutation("— The " . config('app.name') . " Team");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

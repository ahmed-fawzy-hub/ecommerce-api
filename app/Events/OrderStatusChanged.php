<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Log;
use App\Models\Order;

class OrderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $previousStatus;
    public $changedBy;
    /**
     * Create a new event instance.
     */
    public function __construct(Order $order,
        ?string $previousStatus=null,
        ?string $changedBy=null)
    {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
        $this->changedBy = $changedBy;
        $this->order->load(['user','items.product']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' .$this->order->user_id. '.orders'),
            new PrivateChannel('admin.orders')
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    public function broadcastWith(): array
    {
        $broadcastData = [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'current_status' => $this->order->status->value,
            'previous_status' => $this->previousStatus,
            'changed_by' => $this->changedBy,
            'total' => $this->order->total,
            'updated_at' => $this->order->updated_at->toISOString(),
            'user' => [
                'id' => $this->order->user->id,
                'name' => $this->order->user->name,
                'email' => $this->order->user->email,
            ],
            'items_count' => $this->order->items->count(),
            'items_summary' => $this->order->items->take(3)->map(function($item) {
                return [
                'product_name' => $item->product->name,
                'quantity' => $item->quantity,
                ];
            })->toArray(),
        ];
        Log::info('Order status changed broadcast data: ' . json_encode($broadcastData));
        return $broadcastData;
    }   

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;

class OrderStatusHistory extends Model
{
    //fillable
    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'user_id',
        'notes',
    ];
    //cast
    protected $casts = [
        'from_status' => OrderStatus::class,
        'to_status' => OrderStatus::class,
    ];
    //relationship
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

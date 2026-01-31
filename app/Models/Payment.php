<?php

namespace App\Models;

use App\Models\Order;
use App\Models\User;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'provider',
        'payment_intent_id',
        'amount',
        'currency',
        'status',
        'metadata',
        'completed_at',
    ];
    protected $casts = [
        'metadata'=> 'array',
        'completed_at'=> 'datetime',
        'amount'=> 'decimal:2',
        'provider'=>PaymentProvider::class,
        'status'=>PaymentStatus::class,
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function markAsCompleted($paymentIntentId,$metadata=[])
    {
        $this->update([
            'status'=>PaymentStatus::PAID,
            'payment_intent_id'=>$paymentIntentId,
            'completed_at'=>now(),
            'metadata'=>array_merge($this->metadata??[], $metadata),
        ]);
        $this->order->MarkAsPaid($paymentIntentId);
    }   
    public function markAsFailed($metadata=[])
    {
        $this->update([
            'status'=>PaymentStatus::FAILED,
            'metadata'=>array_merge($this->metadata??[], $metadata),
        ]);
        $this->order->MarkAsFailed();
    }   
    public function isFinal()
    {   
        return in_array($this->status, [PaymentStatus::PAID, PaymentStatus::FAILED, PaymentStatus::REFUNDED]);
    }
    
}

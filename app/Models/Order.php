<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Support\Facades\Auth;
use App\Events\OrderStatusChanged;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'shipping_name',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',
        'shipping_phone',
        'subtotal',
        'tax',
        'shipping_cost',
        'total',
        'payment_method',
        'payment_status',
        'order_number',
        'notes',
        'transaction_id',
        'paid_at',
    ];

    protected $casts = [
        'status'=>OrderStatus::class,
        'payment_status'=>PaymentStatus::class,
        'paid_at'=>'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }
    public function transitionTo(OrderStatus $newStatus, ?User $changedBy=null, ?string $notes=null)
    {
        if($this->status===$newStatus){
            return true;
        }
        if(!$this->canTransitionTo($newStatus)){
            return false;
        }
        $oldStatus=$this->status;
        $this->update([
            'status'=>$newStatus,
        ]);
        $this->statusHistory()->create([
            'order_id'=>$this->id,
            'from_status'=>$oldStatus,
            'to_status'=>$newStatus,
            'user_id'=>$changedBy ? $changedBy->id : Auth::id(),
            'notes'=>$notes,
        ]);
        OrderStatusChanged::dispatch($this, $oldStatus->value, $changedBy ??Auth::user()->name);
        
        return true;
    }
    public function getAllowedTransitions()
    {
    return $this->status->getAllowedTransitions();
    }
    public function getLatestStatusHistory()
    {
        return $this->statusHistory()->first();
    }
    public function getOrderNumberAttribute($value)
    {
        $year = date('Y');
        $randomNumber=strtoupper(substr(uniqid(), -6));
        return "ORD-{$year}-{$randomNumber}";
    }
    public function canBeCancelled()
    {
        return in_array($this->status, [OrderStatus::PENDING, OrderStatus::PAID]);
    }
    public function MarkAsPaid($transactionId = null)
    {
        $this->update([
            'status'=>OrderStatus::PAID,
            'payment_status'=>PaymentStatus::PAID,
            'transaction_id'=>$transactionId ?? $this->transaction_id,
            'paid_at'=>now(),
        ]);
    }
    public function MarkAsFailed()
    {
        $this->update([
            'payment_status'=>PaymentStatus::FAILED,
        ]);
    }
    
}

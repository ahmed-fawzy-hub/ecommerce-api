<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Support\Facades\Auth;
use Exception;

class OrderManagmentController extends Controller
{
    //index
    public function index(Request $request)
    {
        $request->validate([
            'status'=>'nullable|in:'.implode(',', OrderStatus::values()),
            'from_date'=>'date',
            'to_date'=>'date',
        ]);
        $query=Order::with('user','items.product');
        if($request->has('status')){
            $query->where('status',$request->status);
        }
        if($request->has('from_date')){
            $query->where('created_at','>=',$request->from_date);
        }
        if($request->has('to_date')){
            $query->where('created_at','<=',$request->to_date);
        }
        $orders=$query->latest()->paginate(15);
        return response()->json([
            'orders'=>$orders,
            'available_status'=>OrderStatus::values(),
        ]);
    }
    //show
    public function show(Order $order)
    {
        $order->load('user','items.product','statusHistory.changedBy');
        return response()->json([
            'order'=>$order,
            'available_transitions'=>$order->getAllowedTransitions(),
        ]);
    }
    public function updateStatus(Order $order,Request $request)
    {
        $request->validate([
            'status'=>'required|string|in:'.implode(',', OrderStatus::values()),
            'notes'=>'nullable|string|max:500',
        ]);
        try{
            $newStatus=OrderStatus::from($request->status);
            $order->transitionTo($newStatus,Auth::user(),$request->notes);
            $order->load('statusHistory.changedBy');
            return response()->json([
                'success'=>true,
                'message'=>"Order status updated successfully to {$newStatus}",
                'order'=>$order,
            ]);
        }catch(Exception $e){
            return response()->json([
                'success'=>false,
                'error'=>$e->getMessage(),
            ],400);
        }
    }   
    public function cancel(Request $request,Order $order)
    {
        $request->validate([
            'notes'=>'required|string|max:500',
        ]);
        try{
            if(!$order->canBeCancelled()){
                return response()->json([
                    'success'=>false,
                    'error'=>'Order cannot be cancelled',
                ],400);
            }
            $order->transitionTo(OrderStatus::CANCELLED,Auth::user(),$request->notes);
            return response()->json([
                'success'=>true,
                'message'=>'Order cancelled successfully',
                'order'=>$order->fresh(['statusHistory.changedBy']),
            ]);
        }catch(Exception $e){
            return response()->json([
                'success'=>false,
                'error'=>$e->getMessage(),
            ],400);
        }
    }
}

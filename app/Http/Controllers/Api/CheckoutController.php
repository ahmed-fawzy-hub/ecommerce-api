<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Notifications\OrderConfirmationNotification;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    //checkout
    public function checkout(Request $request)
    {
        $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:255',
            'shipping_city' => 'required|string|max:255',
            'shipping_state' => 'nullable|string|max:255',
            'shipping_zip' => 'required|string|max:20',
            'shipping_country' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'payment_method' => 'required|in:credit_card,paypal',
            'notes' => 'nullable|string',
        ]);

        $user =$request->user();
        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();
        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty',
            ], 400);
        }
        $subtotal=0;
        $items=[];
        foreach ($cartItems as $item) {
            $product = $item->product;
            if(!$product->is_active){
                return response()->json([
                    'message' => "Product '{$product->name}' is not active",
                ], 400);
            }
            if($product->stock < $item->quantity){
                return response()->json([
                    'message' => "Product stock is not enough for '{$product->name}'",
                ], 400);
            }
            $itemSubTotal = round($product->price * $item->quantity, 2);
            $subtotal += $itemSubTotal;
            $items[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $item->quantity,
                'price' => $product->price,
                'subtotal' => $itemSubTotal,
            ];
        }
        $tax = round($subtotal * 0.08, 2);
        $shippingCost = 5.00;
        $total = round($subtotal + $tax + $shippingCost, 2);
        DB::beginTransaction();
        try {
            $order = new Order([
                'user_id' => $user->id,
                'status' => OrderStatus::PENDING,
                'shipping_name' => $request->shipping_name,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_state' => $request->shipping_state,
                'shipping_zip' => $request->shipping_zip,
                'shipping_country' => $request->shipping_country,
                'shipping_phone' => $request->shipping_phone,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'payment_method' => $request->payment_method,
                'payment_status' => PaymentStatus::PENDING,
                'order_number' => $this->generateOrderNumber(),
                'notes' => $request->notes,
            ]);
            $user->orders()->save($order);
            foreach ($items as $item) {
                $order->items()->create($item);
                Product::where('id', $item['product_id'])->decrement('stock', $item['quantity']);
            }
            Cart::where('user_id', $user->id)->each(function($cartItem){
                $cartItem->delete();
            });
            DB::commit();
            // send the order confirmation mail
            $order->user->notify(new OrderConfirmationNotification($order));
            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order->load('items'),
                'status' => true,
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create order: ' . $th->getMessage(),
                'status' => false,
            ], 500);
        }

    }
    public function orderHistory(Request $request)
    {
        $user = $request->user();
        $orders = $user->orders()->with('items')->get();
        return response()->json([
            'message' => 'Order history',
            'orders' => $orders,
            'status' => true,
        ], 200);
    }
    public function orderDetails(Request $request, $id)
    {
        $user = $request->user();
        $order = $user->orders()->with('items')->find($id);
        if (!$order) {
            return response()->json([
                'message' => 'Order not found',
                'status' => false,
            ], 404);
        }
        return response()->json([
            'message' => 'Order details',
            'order' => $order,
            'status' => true,
        ], 200);
    }

    private function generateOrderNumber()
    {
        return 'ORD-' . strtoupper(Str::random(10));
    }
}

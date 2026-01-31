<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //get user cart
        $user = $request->user();
        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();
        $total = $cartItems->sum(function ($item) {
            return $item->product ? $item->product->price * $item->quantity : 0;
        });
        return response()->json([
            'success' => true,
            'message' => 'Cart items retrieved successfully',
            'cart' => $cartItems,
            'total' => $total,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //get user
        $user = $request->user();
        //get product
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);
        //check if product is already in cart
        $cartItem = Cart::where('user_id', $user->id)->where('product_id', $data['product_id'])->first();
        if ($cartItem) {
            $cartItem->quantity += $data['quantity'];
            $cartItem->save();
            return response()->json([
                'success' => true,
                'message' => 'Product added to cart',
                'cart' => $cartItem,
            ], 200);
        }else{
        //create new cart item
        $cartItem = Cart::create([
            'user_id' => $user->id,
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Product added to cart',
            'cart' => $cartItem,
        ], 201);    
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Cart $cart)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cart $cart)
    {
        //validate
        $data = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);
        //update
        $cart->quantity = $data['quantity'];
        $cart->save();
        return response()->json([
            'success' => true,
            'message' => 'Cart updated successfully',
            'cart_item' => $cart,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cart $cart)
    {
        //delete
        $cart->delete();
        return response()->json([
            'success' => true,
            'message' => 'Cart item deleted successfully',
        ], 200);    
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Payment;
use App\Enums\PaymentProvider;
use App\Enums\PaymentStatus;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook as StripeWebhook;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    //initialize payment
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }
    public function createPayment(Request $request, Order $order)
    {
        $request->validate([
            'payment_method' => 'required|string|in:' . implode(',', PaymentProvider::values()),
        ]);
        if($order->user_id !== $request->user()->id){
            return response()->json([
                'message' => 'Unauthorized user',
            ], 403);
        }
        if(!$order->canAcceptPayment()){
         return response()->json([
            'message' => 'Order cannot be accepted',
         ], 400);   
        }
        $provider=PaymentProvider::from($request->input('provider'));
        if($provider === PaymentProvider::STRIPE){
            return $this->createStripePayment($order);
        }
        else{
            return response()->json([
                'message' => 'Payment provider not supported',
            ], 501);
        }
    }
    protected function createStripePayment(Order $order){
        try{
        $payment=Payment::create([
            'order_id'=>$order->id,
            'user_id'=>$order->user_id,
            'provider'=>PaymentProvider::STRIPE,
            'status'=>PaymentStatus::PENDING,
            'amount'=>$order->total,
            'currency'=>'usd',
            'meta_data'=>[
                'order_number'=>$order->order_number,
                'created_at'=>now()->toIso8601String(),
            ],
        ]);
        $paymentIntent=PaymentIntent::create([
            'amount'=>(int)($order->total * 100),
            'currency'=>'usd',
            'metadata'=>[
                'order_id'=>$order->id,
                'user_id'=>$order->user_id,
            ],
            'description'=>'payment for order #'.$order->order_number,
        ]);
        $payment->update([
            'payment_intent_id'=>$paymentIntent->id,
            'meta_data'=>array_merge($payment->meta_data, [
                'client_secret'=>$paymentIntent->client_secret,
            ]),
        ]);
        return response()->json([
            'status'=>true,
            'client_secret'=>$paymentIntent->client_secret,
            'payment_id'=>$payment->id,
            'publishable_key'=>config('services.stripe.key'),
        ]);
    }
    catch(ApiErrorException $e){
        Log::error('stripe payment error:' . $e->getmessage());
        return response()-json([
            'success'=>false,
            'message'=>'failed to create payment intent.',
            'error'=>$e->getMessage(),
        ],500);
    }
}
    public function confirmPayment(Request $request,$paymentId){
        $payment=Payment::find($paymentId);
        if(!$payment){
            return response()->json([
                'message'=>'Payment not found',
            ],404);
        }
        if($payment->user_id !== $request->user()->id){
            return response()->json([
                'message'=>'Unauthorized user',
            ],403);
        }
        return response()->json([
            'success'=>true,
            'message'=>'Payment confirmed',
            'payment'=>$payment,
            'order'=>$payment->order,
        ],200);
        

    }
    public function stripeWebhook(Request $request){
        $payload=$request->getContent();
        $sigHeader=$request->header('Stripe-Signature');
        $webhookSecret=config('services.stripe.webhook_secret');
        try{
            $event=StripeWebhook::constructFrom($payload,$sigHeader,$webhookSecret);
            switch($event->type){
                case 'payment_intent.succeeded':
                    return $this->handleSuccessfulPayment($event);
                case 'payment_intent.failed':
                    return $this->handleFailedPayment($event);
                default:
                    Log::warning('stripe webhook received unknown event type:' . $event->type);
                    return response()->json([
                        'status'=>'ignored',
                    ]);  
            }
        }
        catch(UnexpectedValueException $e){
            Log::error('stripe webhook error:' . $e->getmessage());
            return response()->json([
                'error'=>'invalid webhook payload',
            ],400);
        }
        catch(SignatureVerificationException $e){
            Log::error('stripe webhook error:' . $e->getmessage());
            return response()->json([
                'error'=>'invalid webhook signature',
            ],400);
        }
    }
    protected function handleSuccessfulPayment($paymentIntent){
        $payment=Payment::where('payment_intent_id',$paymentIntent->id)->first();
        if(!$payment){
            Log::error('payment not found for payment intent:' . $paymentIntent->id);
            return response()->json([
                'error'=>'payment not found',
            ],404);
        }
        $payment->markAsCompleted($paymentIntent->id,[
            'stripe_data'=>[
                'amount'=>$paymentIntent->amount/100,
                'currency'=>$paymentIntent->currency,
                'description'=>$paymentIntent->description,
                'status'=>$paymentIntent->status,
                'created_at'=>now()->toIso8601String(),
            ],
        ]);
        return response()->json([
            'success'=>true,
            'message'=>'Payment completed successfully',
            'payment'=>$payment,
            'order'=>$payment->order,
        ]);    
    }
    protected function handleFailedPayment($paymentIntent){
        $payment=Payment::where('payment_intent_id',$paymentIntent->id)->first();
        if(!$payment){
            Log::error('payment not found for payment intent:' . $paymentIntent->id);
            return response()->json([
                'success'=>false,
                'message'=>'payment not found',
            ],404);
        }
        if(!$payment->isFailed()){
            $payment->markAsFailed($paymentIntent->id,[
            'stripe_data'=>[
                'error'=>$paymentIntent->last_error ? $paymentIntent->last_error : 'unknown error',
                'status'=>$paymentIntent->status,
                'failed_at'=>now()->toIso8601String(),
            ],
        ]);
        return response()->json([
            'success'=>true,
            'message'=>'Payment failed',
            'payment'=>$payment,
            'order'=>$payment->order,
        ]);    
    }
}
}
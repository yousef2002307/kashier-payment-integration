<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function index()  {
        $orders = Order::all(); 
        return view('order.index',compact('orders'));
    }

    public function create()  {
        return view('order.create');
    }

    public function store(Request $request)  {
        $request->validate([
        'product_name' => 'required|string|max:255',
        'price' => 'required|numeric',
        ]);

        Order::create($request->all());
        return redirect()->route('order.index')->with('success', 'Order created successfully.');
    }

    public function initiatePayment($id)  {
        $order = Order::find($id);
        $currency = "EGP"; //your currency

        $kashierOrderHash = $this->generateKashierOrderHash($order,$currency);
        $paymentUrl = "https://checkout.kashier.io/?merchantId=MID-29465-512" .
            "&mode=test" .
            "&orderId={$order->id}" .
            "&amount={$order->price}" .
            "&currency={$currency}" .
            "&hash={$kashierOrderHash}" .
            "&allowedMethods=card,bank_installments,wallet,fawry" . 
            "&merchantRedirect=" . urlencode('http://localhost:8000/callback') .
            "&failureRedirect=" . urlencode('http://localhost:8000/failure') .
            "&redirectMethod=get" .
            "&brandColor=%2300bcbc" . 
            "&display=en";
            return redirect()->away($paymentUrl);
    }
    

    private function generateKashierOrderHash($order,$currency){
        $mid = "MID-29465-512"; //your merchant id
        $amount = $order->price; //eg: 100
        $currency = $currency;
        $orderId = $order->id; //eg: 99, your system order ID
        $secret = "e5624643-2468-4cfd-b3f7-b900b0fc424f";
        $path = "/?payment=".$mid.".".$orderId.".".$amount.".".$currency.(isset( $CustomerReference) ?(".".$CustomerReference):null);
        $hash = hash_hmac( 'sha256' , $path , $secret ,false);
        return $hash;
    }

    public function handleCallback(Request $request)
    {
        // Define your secret API key
        $secret = 'e5624643-2468-4cfd-b3f7-b900b0fc424f';
        // Log the incoming request
        Log::info('Callback hit with parameters: ', $request->all());

        // Build the query string
        $queryString = "";
        foreach ($request->query() as $key => $value) {
            if ($key === "signature" || $key === "mode") {
                continue;
            }
            $queryString .= "&{$key}={$value}";
        }

        // Trim the leading '&'
        $queryString = ltrim($queryString, "&");
        
        // Generate the signature
        $signature = hash_hmac('sha256', $queryString, $secret, false);

        // Check if the signature is valid
        if ($signature === $request->query("signature")) {
            // Signature is valid
            $paymentStatus = $request->query('paymentStatus');
            $orderId = $request->query('merchantOrderId');
            $transactionId = $request->query('transactionId');

            // Update the order based on the payment status
            $order = Order::find($orderId);

            if ($paymentStatus === 'SUCCESS') {
                // Clear the user's cart
                // 'CartModel'::where('user_id', Auth::user()->id)->delete();

                // Update the order status to completed
                $order->update([
                    'payment_transaction_id' => $transactionId,
                    'payment_method' => 'online-payment',
                    'payment_status' => "Completed",
                ]);


                // Send confirmation email
                try {
                    // Mail::to('youmail@gmail.com')->send('new Mail($order)');
                } catch (\Exception $e) {
                    Log::error('Email sending failed: ', ['error' => $e->getMessage()]);
                }
                return redirect('/orders')->with('status','payment done successfully');

            }elseif ( $paymentStatus === 'CANCELLED') {

                // Update the order status to cancelled
                $order->update([
                    'payment_transaction_id' => $transactionId,
                    'payment_method' => 'online-payment',
                    'payment_status' => "Cancelled"
                ]);
                return redirect('/orders')->with('status', 'Payment cancelled. Please try again.');
            }
             else {

                // Update the order status to failed
                $order->update([
                    'payment_transaction_id' => $transactionId,
                    'payment_method' => 'online-payment',
                    'payment_status' => "Failed"
                ]);
                return redirect('/orders')->with('status', 'Payment failed. Please try again.');
            }

            // Redirect to the thank-you page
            return redirect('/thankyou');
        } else {
            // Invalid signature
            Log::error('Invalid signature: ', $request->all());
            return redirect('/orders')->with('status', 'Invalid signature. Please try again.');
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}





    // public function initiatePayment(Request $request)
    // {

    //     try {

    //     $userId = Auth::user()->id;
    //     $order = Order::where('user_id', $userId)
    //     ->where('payment_status', '!=', 'Completed') // Ensure we get orders that are not completed
    //     ->whereDate('created_at', Carbon::now()) // Check for today's orders
    //     ->latest() // Get the latest order
    //     ->first();
    
    //     if (!$order) {
    //         return redirect()->back()->with('error', 'No order found.');
    //     }
    
    //     $totalAmount = $this->totalProductAmount(); // Calculate total amount for the order
    //     $orderId = $order->id;
    
    //     // // Generate the payment hash
    //     $hash = $this->generateKashierOrderHash($orderId, $totalAmount, 'EGP');
        
    //     // Prepare the payment URL with all necessary data
    //     $paymentUrl = "https://checkout.kashier.io/?merchantId=MID-29264-164" .
    //     "&mode=test" .
    //     "&orderId={$orderId}" .
    //     "&amount={$totalAmount}" .
    //     "&currency=EGP" .
    //     "&hash={$hash}" .
    //     "&allowedMethods=card" . 
    //     "&merchantRedirect=" . urlencode('http://localhost:8000/callback') .
    //     "&failureRedirect=" . urlencode('http://localhost:8000/failure') .
    //     "&redirectMethod=get" .
    //     "&brandColor=%2300bcbc" . 
    //     "&display=en";

    //     // Redirect the user to the Kashier payment page
            
    //     return response()->json(['paymentUrl' => $paymentUrl]);
    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }
    

    // private function generateKashierOrderHash($orderId, $amount, $currency)
    // {
    //     $mid = 'MID-29264-164'; 
    //     $secret = '04d5f792-d1b9-468f-881f-b66212303b75'; // Your secret key
    //     $path = "/?payment={$mid}.{$orderId}.{$amount}.{$currency}";
    //     return hash_hmac('sha256', $path, $secret, false);
    // }

  

    // public function handleCallback(Request $request)
    // {
    //     // Define your secret API key
    //     $secret = '04d5f792-d1b9-468f-881f-b66212303b75';

    //     // Log the incoming request
    //     Log::info('Callback hit with parameters: ', $request->all());

    //     // Build the query string
    //     $queryString = "";
    //     foreach ($request->query() as $key => $value) {
    //         if ($key === "signature" || $key === "mode") {
    //             continue;
    //         }
    //         $queryString .= "&{$key}={$value}";
    //     }

    //     // Trim the leading '&'
    //     $queryString = ltrim($queryString, "&");
        
    //     // Generate the signature
    //     $signature = hash_hmac('sha256', $queryString, $secret, false);

    //     // Check if the signature is valid
    //     if ($signature === $request->query("signature")) {
    //         // Signature is valid
    //         $paymentStatus = $request->query('paymentStatus');
    //         $orderId = $request->query('merchantOrderId');
    //         $transactionId = $request->query('transactionId');

    //         // Update the order based on the payment status
    //         $order = Order::find($orderId);

    //         if ($paymentStatus === 'SUCCESS') {
    //             // Clear the user's cart
    //             Cart::where('user_id', Auth::user()->id)->delete();

    //             // Update the order status to completed
    //             $order->update([
    //                 'payment_id' => $transactionId,
    //                 'payment_status' => "Completed",
    //                 'status_message' => "Completed"
    //             ]);

    //             // Send confirmation email
    //             try {
    //                 Mail::to('maherfared@gmail.com')->send(new PlaceOrderMailable($order));
    //             } catch (\Exception $e) {
    //                 Log::error('Email sending failed: ', ['error' => $e->getMessage()]);
    //             }

    //         } else {
    //             // Update the order status to failed
    //             $order->update([
    //                 'payment_id' => $transactionId,
    //                 'payment_status' => "Failed"
    //             ]);
    //             return redirect('/cart')->with('message', 'Payment failed. Please try again.');
    //         }

    //         // Redirect to the thank-you page
    //         return redirect('/thankyou');
    //     } else {
    //         // Invalid signature
    //         Log::error('Invalid signature: ', $request->all());
    //         return redirect('/cart')->with('message', 'Invalid signature. Please try again.');
    //     }
    // }

<?php

namespace App\Http\Controllers;

use App\Models\LocalOrder;
use Illuminate\Http\Request;

class PayHereNotifyController extends Controller
{
    public function handle(Request $request)
    {
        $merchantId = $request->input('merchant_id');
        $orderId = $request->input('order_id');
        $payhereAmount = $request->input('payhere_amount');
        $payhereCurrency = $request->input('payhere_currency');
        $statusCode = $request->input('status_code');
        $md5sig = $request->input('md5sig');
        $paymentId = $request->input('payment_id');

        $merchantSecret = config('services.payhere.merchant_secret');

        $localMd5sig = strtoupper(
            md5(
                $merchantId.
                $orderId.
                $payhereAmount.
                $payhereCurrency.
                $statusCode.
                strtoupper(md5($merchantSecret))
            )
        );

        if ($localMd5sig !== $md5sig) {
            return response('Invalid signature', 403);
        }

        $order = LocalOrder::where('order_number', $orderId)->first();

        if (! $order) {
            return response('Order not found', 404);
        }

        // status_code 2 = success, -1 = pending, -2 = canceled, -3 = failed/chargedback
        if ((int) $statusCode === 2) {
            $order->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'payhere_payment_id' => $paymentId,
            ]);
        } elseif (in_array((int) $statusCode, [-2, -3], true)) {
            $order->update([
                'payment_status' => 'failed',
                'payhere_payment_id' => $paymentId,
            ]);
        }

        return response('OK');
    }
}

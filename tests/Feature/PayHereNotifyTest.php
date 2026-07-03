<?php

use App\Models\LocalOrder;

it('sets order status to confirmed when payhere payment succeeds', function () {
    $order = LocalOrder::factory()->create([
        'status' => 'pending',
        'payment_method' => 'payhere',
        'payment_status' => 'pending',
        'total' => 500.00,
    ]);

    $merchantSecret = config('services.payhere.merchant_secret');
    $merchantId = config('services.payhere.merchant_id');

    $statusCode = '2'; // success

    $md5sig = strtoupper(
        md5(
            $merchantId.
            $order->order_number.
            '500.00'.
            'LKR'.
            $statusCode.
            strtoupper(md5($merchantSecret))
        )
    );

    $this->post(route('payhere.notify'), [
        'merchant_id' => $merchantId,
        'order_id' => $order->order_number,
        'payhere_amount' => '500.00',
        'payhere_currency' => 'LKR',
        'status_code' => $statusCode,
        'md5sig' => $md5sig,
        'payment_id' => 'PAY-123',
    ])->assertOk();

    $order->refresh();

    expect($order->status)->toBe('confirmed')
        ->and($order->payment_status)->toBe('paid')
        ->and($order->payhere_payment_id)->toBe('PAY-123');
});

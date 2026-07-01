<?php

namespace App\Jobs;

use App\Models\LocalOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Twilio\Rest\Client;

class SendOrderNotificationToOwner implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public LocalOrder $order)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $accountSid = config('services.twilio.account_sid');
        $authToken = config('services.twilio.auth_token');
        $whatsappFrom = config('services.twilio.whatsapp_from');
        $ownerWhatsapp = config('services.twilio.owner_whatsapp');

        if (! $accountSid || ! $authToken || ! $whatsappFrom || ! $ownerWhatsapp) {
            return;
        }

        $twilio = new Client($accountSid, $authToken);

        // Format the order items summary
        $itemsSummary = $this->order->items
            ->map(fn ($item) => "{$item->product_name} × {$item->quantity}")
            ->implode(', ');

        $dashboardUrl = route('admin.orders');

        // Create the WhatsApp message
        $message = "New Order Received!\n\n"
            ."Order #: {$this->order->order_number}\n"
            ."Customer: {$this->order->customer_name}\n"
            ."Phone: {$this->order->customer_phone}\n"
            ."Items: {$itemsSummary}\n"
            ."Total: LKR {$this->order->total}\n"
            ."Payment: {$this->getPaymentMethod()}\n\n"
            ."Check the dashboard for more details:\n{$dashboardUrl}";

        try {
            $twilio->messages->create(
                "whatsapp:{$ownerWhatsapp}",
                [
                    'from' => "whatsapp:{$whatsappFrom}",
                    'body' => $message,
                ]
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send WhatsApp notification: '.$e->getMessage());
        }
    }

    /**
     * Get payment method display text.
     */
    private function getPaymentMethod(): string
    {
        return match ($this->order->payment_method) {
            'payhere' => 'Online Payment (PayHere)',
            'cod' => 'Cash on Delivery',
            default => $this->order->payment_method,
        };
    }
}

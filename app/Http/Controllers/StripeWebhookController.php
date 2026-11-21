<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    public function handleCustomerSubscriptionUpdated(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        Log::info('Stripe subscription updated', [
            'customer' => $payload['data']['object']['customer'] ?? null,
            'status'   => $payload['data']['object']['status'] ?? null,
        ]);

        return $response;
    }

    public function handleCustomerSubscriptionDeleted(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        Log::info('Stripe subscription cancelled', [
            'customer' => $payload['data']['object']['customer'] ?? null,
        ]);

        return $response;
    }

    public function handleInvoicePaymentSucceeded(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        Log::info('Stripe invoice payment succeeded', [
            'customer' => $payload['data']['object']['customer'] ?? null,
            'amount'   => $payload['data']['object']['amount_paid'] ?? null,
        ]);

        return $this->successMethod();
    }

    public function handleInvoicePaymentFailed(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        Log::warning('Stripe invoice payment failed', [
            'customer' => $payload['data']['object']['customer'] ?? null,
            'amount'   => $payload['data']['object']['amount_due'] ?? null,
        ]);

        return $this->successMethod();
    }
}

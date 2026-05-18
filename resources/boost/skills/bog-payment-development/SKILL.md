---
name: bog-payment-development
description: "Use when integrating, configuring, or troubleshooting the Bank of Georgia payment gateway in a Laravel app via the redberryproducts/laravel-bog-payment package. Trigger whenever the query mentions BOG, Bank of Georgia, the Pay or Transaction facades from RedberryProducts\\LaravelBogPayment, the TransactionStatusUpdated event, methods like process(), saveCard(), chargeCard(), deleteCard(), subscribe(), chargeSubscription(), or the bog-payment.callback route. Tasks include initiating standard payments, saving cards for reuse, charging saved cards or subscriptions without user interaction, building the payment payload (orderId/amount/buyer/redirectUrl/callbackUrl), publishing and configuring config/bog-payment.php, setting the BOG_CLIENT_ID/BOG_SECRET/BOG_PUBLIC_KEY env vars, wiring a listener for TransactionStatusUpdated, and retrieving transaction status with Transaction::get(). Do not trigger for unrelated payment gateways (Stripe, PayPal, TBC) or generic Laravel payment questions."
license: MIT
metadata:
  author: redberryproducts
---
# BOG Payment Gateway

The `redberryproducts/laravel-bog-payment` package wraps the Bank of Georgia payment API. The public surface is two facades — `Pay` for initiating payments / cards / subscriptions, and `Transaction` for status lookups — plus a single dispatched event, `TransactionStatusUpdated`.

## Installation & Configuration

- `composer require redberryproducts/laravel-bog-payment`
- `php artisan vendor:publish --tag="bog-payment-config"` — publishes `config/bog-payment.php`
- Required env: `BOG_CLIENT_ID`, `BOG_SECRET`, `BOG_PUBLIC_KEY`
- Optional env: `BOG_CALLBACK_URL`, `BOG_REDIRECT_SUCCESS`, `BOG_REDIRECT_FAIL`, `BOG_BASE_URL` (defaults to `https://api.bog.ge/payments/v1`)
- Inspect the live config with the Boost MCP tool `config-show bog-payment`.
- The current `BOG_PUBLIC_KEY` is published at https://api.bog.ge/docs/payments/standard-process/callback.

## Standard Payment

<!-- Standard Payment -->
```php
use RedberryProducts\LaravelBogPayment\Facades\Pay;

$payment = Pay::orderId($order->id)
    ->amount($order->total)                              // GEL by default
    ->redirectUrl(route('orders.show', $order))
    ->process();

// $payment is a PaymentResponseData DTO (ArrayAccess) with: id, redirect_url, details_url
return redirect($payment['redirect_url']);
```

Rules:
- `orderId()` MUST be called before `amount()` — otherwise `amount()` throws `RuntimeException`.
- `amount($total, $currency = 'GEL', $basket = [])` — when `$basket` is empty, a one-item basket is auto-generated from `$total` and the order id.
- `redirectUrl($url)` sets both success and fail URLs to the same value. Use `redirectUrls($failUrl, $successUrl)` to split them.

## Save Card During Payment

<!-- Save Card -->
```php
$response = Pay::orderId($order->id)
    ->amount($order->total)
    ->redirectUrl(route('orders.show', $order))
    ->saveCard();   // terminal — do NOT chain ->process() after it

// Persist $response['id'] as the parent transaction id; you'll need it to charge the card later.
```

## Charge a Saved Card (no user interaction)

<!-- Charge Saved Card -->
```php
$response = Pay::orderId($order->id)
    ->amount($order->total)
    ->chargeCard($parentTransactionId);
```

## Delete a Saved Card

```php
Pay::deleteCard($cardId);
```

## Subscriptions

<!-- Register Subscription -->
```php
$response = Pay::orderId($order->id)
    ->amount($order->total)
    ->redirectUrl(route('orders.show', $order))
    ->subscribe();

$subscriptionId = $response['id'];   // persist this
```

<!-- Charge Subscription -->
```php
$response = Pay::orderId($order->id)
    ->amount($order->total)
    ->chargeSubscription($subscriptionId);   // no user interaction
```

## Buyer Details

```php
Pay::orderId($order->id)
    ->amount($order->total)
    ->buyer([
        'full_name'    => 'John Doe',
        'masked_email' => 'jo**@example.com',
        'masked_phone' => '5995****10',
    ])
    ->process();

// Or set fields individually:
Pay::orderId($order->id)
    ->amount($order->total)
    ->buyerName('John Doe')
    ->buyerEmail('jo**@example.com')
    ->buyerPhone('5995****10')
    ->process();
```

## Callback Handling

The package registers `POST /bog-payment/payment/callback` (route name `bog-payment.callback`). Verify with `php artisan route:list --name=bog-payment`. The controller verifies the BOG signature with `BOG_PUBLIC_KEY` (RSA-SHA256) and dispatches `RedberryProducts\LaravelBogPayment\Events\TransactionStatusUpdated`.

Generate a listener:

```bash
php artisan make:listener HandleTransactionStatusUpdate \
    --event="RedberryProducts\LaravelBogPayment\Events\TransactionStatusUpdated"
```

```php
use RedberryProducts\LaravelBogPayment\Events\TransactionStatusUpdated;

class HandleTransactionStatusUpdate
{
    public function handle(TransactionStatusUpdated $event): void
    {
        // $event->transaction is the raw payload array from BOG
    }
}
```

Laravel auto-discovers the listener as long as it lives under `app/Listeners` and type-hints the event in `handle()`.

## Transaction Status Lookup

```php
use RedberryProducts\LaravelBogPayment\Facades\Transaction;

$details = Transaction::get($orderId);   // array
```

Response shape: https://api.bog.ge/docs/payments/standard-process/get-payment-details

## Verification

1. `php artisan config:show bog-payment` — confirm credentials and URLs are wired (use Boost's `config-show` MCP tool for the same result).
2. `php artisan route:list --name=bog-payment` — confirm the callback route is registered.
3. After a sandbox payment, confirm `TransactionStatusUpdated` fires (e.g. temporary `Log::info($event->transaction)` in the listener).

## Common Pitfalls

- Calling `amount()` before `orderId()` throws `RuntimeException`.
- Chaining `->saveCard()->process()` or `->subscribe()->process()` — both are terminal and already return the `PaymentResponseData`.
- Forgetting `BOG_PUBLIC_KEY` — callbacks that fail signature verification are silently rejected, so `TransactionStatusUpdated` never fires.
- Persisting only the local order id and discarding the `id` returned from `saveCard()` / `subscribe()` — that BOG-side id is what `chargeCard()` / `chargeSubscription()` need.
- Hardcoding `https://api.bog.ge/payments/v1` instead of using `config('bog-payment.base_url')` / `BOG_BASE_URL` — blocks switching to BOG's sandbox.
- Using `$response->id` / `$response->redirect_url` — properties are private. Use array access (`$response['id']`) or treat it as `PaymentResponseData`'s ArrayAccess interface.

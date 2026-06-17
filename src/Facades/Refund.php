<?php

namespace RedberryProducts\LaravelBogPayment\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \RedberryProducts\LaravelBogPayment\Refund orderId(string $orderId)
 * @method static \RedberryProducts\LaravelBogPayment\Refund amount(float $amount)
 * @method static \RedberryProducts\LaravelBogPayment\DTO\RefundResponseData process()
 *
 * @see \RedberryProducts\LaravelBogPayment\Refund
 */
class Refund extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RedberryProducts\LaravelBogPayment\Refund::class;
    }
}


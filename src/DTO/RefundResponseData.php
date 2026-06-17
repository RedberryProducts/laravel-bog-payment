<?php

namespace RedberryProducts\LaravelBogPayment\DTO;

use ArrayAccess;
use RedberryProducts\LaravelBogPayment\Traits\Utils\Arrayable;

final class RefundResponseData implements ArrayAccess
{
    use Arrayable;

    private mixed $order_id;

    private mixed $amount;

    private ?string $status;

    private ?string $message;

    public function __construct($order_id, $amount, $status = null, $message = null)
    {
        $this->order_id = $order_id;
        $this->amount = $amount;
        $this->status = $status;
        $this->message = $message;
    }
}


<?php

namespace RedberryProducts\LaravelBogPayment;

use RedberryProducts\LaravelBogPayment\Contracts\RefundContract;
use RedberryProducts\LaravelBogPayment\DTO\RefundResponseData;

class Refund implements RefundContract
{
    public ApiClient $apiClient;

    private ?string $orderId = null;

    private ?float $amount = null;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * Set the order ID to refund
     *
     * @param  string  $orderId
     * @return $this
     */
    public function orderId(string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Set the amount to refund
     *
     * @param  float  $amount
     * @return $this
     */
    public function amount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Process the refund request
     *
     * @return RefundResponseData
     *
     * @throws \Exception
     */
    public function process(): RefundResponseData
    {
        if (empty($this->orderId)) {
            throw new \Exception('Order ID is required for refund');
        }

        if (empty($this->amount)) {
            throw new \Exception('Amount is required for refund');
        }

        $payload = [
            'amount' => (string) $this->amount,
        ];

        $response = $this->apiClient->post("/payment/refund/{$this->orderId}", $payload);

        // Store values before reset for the response
        $orderId = $this->orderId;
        $amount = $this->amount;

        // Reset the state after processing
        $this->reset();

        return new RefundResponseData(
            order_id: $orderId,
            amount: $amount,
            status: $response['status'] ?? null,
            message: $response['message'] ?? null
        );
    }

    /**
     * Reset the refund state
     *
     * @return void
     */
    private function reset(): void
    {
        $this->orderId = null;
        $this->amount = null;
    }
}


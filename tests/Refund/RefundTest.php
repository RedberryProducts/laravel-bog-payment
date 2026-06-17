<?php

use RedberryProducts\LaravelBogPayment\ApiClient;
use RedberryProducts\LaravelBogPayment\Refund;

beforeEach(function () {
    $this->apiClient = Mockery::mock(ApiClient::class);
    $this->refund = new Refund($this->apiClient);
});

it('can set order id', function () {
    $result = $this->refund->orderId('test-order-123');

    expect($result)->toBeInstanceOf(Refund::class);
});

it('can set amount', function () {
    $result = $this->refund->amount(10.5);

    expect($result)->toBeInstanceOf(Refund::class);
});

it('can chain order id and amount', function () {
    $result = $this->refund->orderId('test-order-123')->amount(10.5);

    expect($result)->toBeInstanceOf(Refund::class);
});

it('processes refund and returns response', function () {
    $this->refund->orderId('test-order-123')->amount(10.5);

    $mockResponse = [
        'order_id' => 'test-order-123',
        'amount' => '10.5',
        'status' => 'success',
        'message' => 'Refund processed successfully',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-123', ['amount' => '10.5'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('order_id', 'test-order-123')
        ->toHaveKey('amount', 10.5)
        ->toHaveKey('status', 'success')
        ->toHaveKey('message', 'Refund processed successfully');
});

it('throws exception when order id is missing', function () {
    $this->refund->amount(10.5);

    expect(fn () => $this->refund->process())
        ->toThrow(Exception::class, 'Order ID is required for refund');
});

it('throws exception when amount is missing', function () {
    $this->refund->orderId('test-order-123');

    expect(fn () => $this->refund->process())
        ->toThrow(Exception::class, 'Amount is required for refund');
});

it('handles partial refund', function () {
    $this->refund->orderId('test-order-456')->amount(5.25);

    $mockResponse = [
        'order_id' => 'test-order-456',
        'amount' => '5.25',
        'status' => 'success',
        'message' => 'Partial refund processed',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-456', ['amount' => '5.25'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('order_id', 'test-order-456')
        ->toHaveKey('amount', 5.25)
        ->toHaveKey('status', 'success');
});

it('handles full refund', function () {
    $this->refund->orderId('test-order-789')->amount(100.00);

    $mockResponse = [
        'order_id' => 'test-order-789',
        'amount' => '100.00',
        'status' => 'success',
        'message' => 'Full refund processed',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-789', ['amount' => '100.0'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('order_id', 'test-order-789')
        ->toHaveKey('amount', 100.00)
        ->toHaveKey('status', 'success');
});

it('handles failed refund response', function () {
    $this->refund->orderId('test-order-fail')->amount(25.00);

    $mockResponse = [
        'order_id' => 'test-order-fail',
        'amount' => '25.00',
        'status' => 'failed',
        'message' => 'Insufficient funds or invalid order',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-fail', ['amount' => '25.0'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('status', 'failed')
        ->toHaveKey('message', 'Insufficient funds or invalid order');
});

it('resets state after processing', function () {
    $this->refund->orderId('test-order-123')->amount(10.5);

    $mockResponse = [
        'order_id' => 'test-order-123',
        'amount' => '10.5',
        'status' => 'success',
    ];

    $this->apiClient->shouldReceive('post')
        ->once()
        ->andReturn($mockResponse);

    $this->refund->process();

    // After processing, the state should be reset
    // Attempting to process again should throw an exception
    expect(fn () => $this->refund->process())
        ->toThrow(Exception::class);
});

it('handles decimal amounts correctly', function () {
    $this->refund->orderId('test-order-decimal')->amount(15.99);

    $mockResponse = [
        'order_id' => 'test-order-decimal',
        'amount' => '15.99',
        'status' => 'success',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-decimal', ['amount' => '15.99'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('amount', 15.99);
});

it('handles zero decimal amounts', function () {
    $this->refund->orderId('test-order-whole')->amount(50.0);

    $mockResponse = [
        'order_id' => 'test-order-whole',
        'amount' => '50.0',
        'status' => 'success',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-whole', ['amount' => '50.0'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('amount', 50.0);
});


it('handles very small refund amounts', function () {
    $this->refund->orderId('test-order-small')->amount(0.01);

    $mockResponse = [
        'order_id' => 'test-order-small',
        'amount' => '0.01',
        'status' => 'success',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-small', ['amount' => '0.01'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('amount', 0.01)
        ->toHaveKey('status', 'success');
});

it('handles large refund amounts', function () {
    $this->refund->orderId('test-order-large')->amount(99999.99);

    $mockResponse = [
        'order_id' => 'test-order-large',
        'amount' => '99999.99',
        'status' => 'success',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-large', ['amount' => '99999.99'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('amount', 99999.99)
        ->toHaveKey('status', 'success');
});

it('handles order IDs with special characters', function () {
    $specialOrderId = 'order-2024-BOG-12345_TEST';
    $this->refund->orderId($specialOrderId)->amount(25.00);

    $mockResponse = [
        'order_id' => $specialOrderId,
        'amount' => '25.00',
        'status' => 'success',
    ];

    $this->apiClient->shouldReceive('post')
        ->with("/payment/refund/{$specialOrderId}", ['amount' => '25.0'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('order_id', $specialOrderId);
});

it('handles refund with minimal response data', function () {
    $this->refund->orderId('test-order-minimal')->amount(10.00);

    $mockResponse = [
        'status' => 'pending',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-minimal', ['amount' => '10.0'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('order_id', 'test-order-minimal')
        ->toHaveKey('amount', 10.00)
        ->toHaveKey('status', 'pending');
});

it('can be reused after successful refund', function () {
    // First refund
    $this->refund->orderId('order-1')->amount(10.00);

    $mockResponse1 = [
        'order_id' => 'order-1',
        'amount' => '10.0',
        'status' => 'success',
    ];

    $this->apiClient->shouldReceive('post')
        ->once()
        ->with('/payment/refund/order-1', ['amount' => '10.0'])
        ->andReturn($mockResponse1);

    $response1 = $this->refund->process();

    expect($response1)
        ->toHaveKey('order_id', 'order-1');

    // Second refund (reuse same instance)
    $this->refund->orderId('order-2')->amount(20.00);

    $mockResponse2 = [
        'order_id' => 'order-2',
        'amount' => '20.0',
        'status' => 'success',
    ];

    $this->apiClient->shouldReceive('post')
        ->once()
        ->with('/payment/refund/order-2', ['amount' => '20.0'])
        ->andReturn($mockResponse2);

    $response2 = $this->refund->process();

    expect($response2)
        ->toHaveKey('order_id', 'order-2');
});

it('handles timeout or pending status', function () {
    $this->refund->orderId('test-order-pending')->amount(50.00);

    $mockResponse = [
        'order_id' => 'test-order-pending',
        'amount' => '50.00',
        'status' => 'pending',
        'message' => 'Refund is being processed',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-pending', ['amount' => '50.0'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('status', 'pending')
        ->toHaveKey('message', 'Refund is being processed');
});

it('handles insufficient funds error', function () {
    $this->refund->orderId('test-order-insufficient')->amount(100.00);

    $mockResponse = [
        'order_id' => 'test-order-insufficient',
        'amount' => '100.00',
        'status' => 'failed',
        'message' => 'Insufficient funds for refund',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-insufficient', ['amount' => '100.0'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('status', 'failed')
        ->toHaveKey('message', 'Insufficient funds for refund');
});

it('handles already refunded error', function () {
    $this->refund->orderId('test-order-duplicate')->amount(30.00);

    $mockResponse = [
        'order_id' => 'test-order-duplicate',
        'amount' => '30.00',
        'status' => 'failed',
        'message' => 'Order has already been fully refunded',
    ];

    $this->apiClient->shouldReceive('post')
        ->with('/payment/refund/test-order-duplicate', ['amount' => '30.0'])
        ->andReturn($mockResponse);

    $response = $this->refund->process();

    expect($response)
        ->toHaveKey('status', 'failed')
        ->toHaveKey('message', 'Order has already been fully refunded');
});

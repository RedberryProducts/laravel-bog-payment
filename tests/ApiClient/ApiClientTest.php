<?php

use Illuminate\Support\Facades\Http;
use RedberryProducts\LaravelBogPayment\ApiClient;

beforeEach(function () {
    $this->apiClient = new ApiClient;
});

it('throws an exception if authentication fails', function () {
    Http::fake([
        'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token' => Http::response([], 401),
    ]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('Authentication failed with Bank of Georgia.');

    // Call the method and assert the exception
    $this->apiClient->authenticate();
});

it('sends a POST request with correct URL and token', function () {
    Http::fake([
        'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token' => Http::response([
            'access_token' => 'fake-access-token',
        ], 200),
        config('bog-payment.base_url').'*' => Http::response([
            'status' => 'success',
        ], 200),
    ]);

    $this->apiClient->post('/payments', ['amount' => 100]);

    $recorded = Http::recorded();

    [$postRequest, $postResponse] = $recorded[1];

    expect($postRequest->data())->toBeArray()->toMatchArray(['amount' => 100]);
    expect($postResponse->json())->toBeArray()->toMatchArray(['status' => 'success']);
});

it('throws an exception on failed POST request', function () {
    Http::fake([
        'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token' => Http::response([
            'access_token' => 'fake-access-token',
        ], 200),
        config('bog-payment.base_url').'*' => Http::response([], 500),
    ]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('API request failed with Bank of Georgia.');

    $this->apiClient->post('/payments', ['amount' => 100]);
});

it('sends a GET request with correct URL and token', function () {
    Http::fake([
        'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token' => Http::response([
            'access_token' => 'fake-access-token',
        ], 200),
        config('bog-payment.base_url').'*' => Http::response([
            'status' => 'success',
        ], 200),
    ]);

    $this->apiClient->get('/transactions', ['transaction_id' => 123]);

    $recorded = Http::recorded();

    [$getRequest, $getResponse] = $recorded[1];

    expect($getRequest->data())->toBeArray()->toMatchArray(['transaction_id' => 123]);
    expect($getResponse->json())->toBeArray()->toMatchArray(['status' => 'success']);
});

it('throws an exception on failed GET request', function () {
    Http::fake([
        'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token' => Http::response([
            'access_token' => 'fake-access-token',
        ], 200),
        'https://api.bog.ge/transactions' => Http::response([], 500),
    ]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage('API request failed with Bank of Georgia.');

    $this->apiClient->get('/transactions', ['transaction_id' => 123]);
});

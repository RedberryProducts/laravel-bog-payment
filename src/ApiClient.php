<?php

namespace RedberryProducts\LaravelBogPayment;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @method mixed get($endpoint, $payload = [])
 * @method mixed put($endpoint, $payload = [])
 * @method mixed post($endpoint, $payload = [])
 * @method mixed delete($endpoint, $payload = [])
 */
class ApiClient
{
    private string $baseUrl;

    private string $clientId;

    private string $clientSecret;

    private ?string $accessToken = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('bog-payment.base_url'), '/');
        $this->clientId = config('bog-payment.client_id');
        $this->clientSecret = config('bog-payment.secret');
    }

    /**
     * Authenticate and retrieve the access token.
     *
     * @throws RequestException|ConnectionException
     */
    public function authenticate(): void
    {
        try {
            // This URL is unique for authentication, not using baseUrl
            $authUrl = 'https://oauth2.bog.ge/auth/realms/bog/protocol/openid-connect/token';

            $response = Http::asForm()->withBasicAuth($this->clientId, $this->clientSecret)
                ->post($authUrl, [
                    'grant_type' => 'client_credentials',
                ]);
            if ($response->successful()) {
                $this->accessToken = $response->json()['access_token'];
            } else {
                // Log error for debugging
                Log::error('BOG Authentication Failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new Exception('Authentication failed with Bank of Georgia.');
            }
        } catch (Exception $e) {
            Log::error('Authentication Exception: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Ensure the service is authenticated before making requests.
     */
    private function ensureServiceIsAuthenticated(): void
    {
        if (empty($this->accessToken)) {
            $this->authenticate();
        }
    }

    /**
     * Handle the response from the HTTP client.
     *
     * @param  Response  $response
     *
     * @throws RequestException|Exception
     */
    private function handleResponse($response): mixed
    {
        if ($response->successful()) {
            return $response->json();
        }

        // Log error details
        Log::error('API Request Failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new Exception('API request failed with Bank of Georgia.'.$response->body());
    }

    /**
     * Handle dynamic method calls to the client.
     *
     * @throws RequestException
     * @throws Exception
     */
    public function __call($name, $arguments)
    {
        if (! in_array($name, ['get', 'post', 'put', 'delete'])) {
            throw new Exception('Method not allowed');
        }
        $endpoint = $arguments[0];
        $payload = $arguments[1] ?? [];

        $this->ensureServiceIsAuthenticated();

        try {
            $url = $this->baseUrl.$endpoint;

            $response = Http::withToken($this->accessToken)
                ->{$name}($url, $payload);

            return $this->handleResponse($response);
        } catch (Exception $e) {
            Log::error('GET Request Exception: '.$e->getMessage());
            throw $e;
        }
    }
}

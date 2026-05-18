<?php

namespace RedberryProducts\LaravelBogPayment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use RedberryProducts\LaravelBogPayment\Events\TransactionStatusUpdated;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StatusCallbackController extends Controller
{
    /**
     * @throws HttpException
     */
    public function __invoke(Request $request)
    {
        $requestBody = $request->getContent();

        $request->validate([
            'body' => 'required|array',
        ]);

        $signature = $request->header('Callback-Signature');
        $publicKey = config('bog-payment.public_key');

        $this->ensureSignatureIsValid($requestBody, $signature, $publicKey);

        event(new TransactionStatusUpdated($request->input('body')));

        return $request->input('body');
    }

    /**
     * Verifies the signature using the provided public key.
     *
     * @param  string  $data  The data to verify.
     * @param  string|null  $signature  The provided signature.
     * @param  string|null  $publicKey  The public key to use for verification.
     */
    private function verifySignature(string $data, ?string $signature, ?string $publicKey): bool
    {
        $decodedSignature = base64_decode($signature);

        $publicKey = openssl_pkey_get_public($publicKey);
        $verified = openssl_verify($data, $decodedSignature, $publicKey, 'RSA-SHA256');

        return $verified === 1;
    }

    /**
     * @throws HttpException
     */
    private function ensureSignatureIsValid($body, $signature, $publicKey)
    {
        if (! $this->verifySignature($body, $signature, $publicKey)) {
            throw new HttpException(401, 'Invalid Signature');
        }
    }
}

<?php

namespace ShahariarAhmad\CourierFraudCheckerBd\Services;

use Illuminate\Support\Facades\Http;
use ShahariarAhmad\CourierFraudCheckerBd\Helpers\CourierFraudCheckerHelper;

class PathaoService
{
    protected string $username;
    protected string $password;

    public function __construct()
    {
        CourierFraudCheckerHelper::checkRequiredConfig([
            'courier-fraud-checker-bd.pathao.user',
            'courier-fraud-checker-bd.pathao.password',
        ]);

        $this->username = config('courier-fraud-checker-bd.pathao.user');
        $this->password = config('courier-fraud-checker-bd.pathao.password');
    }

    public function pathao($phoneNumber)
    {
        CourierFraudCheckerHelper::validatePhoneNumber($phoneNumber);

        $response = Http::post('https://merchant.pathao.com/api/v1/login', [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        if (!$response->successful()) {
            return ['error' => 'Failed to authenticate with Pathao'];
        }

        $data = $response->json();
        $accessToken = trim($data['access_token'] ?? '');

        if (!$accessToken) {
            return ['error' => 'No access token received from Pathao'];
        }

        $responseAuth = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post('https://merchant.pathao.com/api/v1/user/success', [
            'phone' => $phoneNumber,
        ]);

        if (!$responseAuth->successful()) {
            return ['error' => 'Failed to retrieve customer data', 'status' => $responseAuth->status()];
        }

        $object = $responseAuth->json();

        return [
            'success' => $object['data']['customer']['successful_delivery'] ?? 0,
            'cancel' => ($object['data']['customer']['total_delivery'] ?? 0) - ($object['data']['customer']['successful_delivery'] ?? 0),
            'total' => $object['data']['customer']['total_delivery'] ?? 0,
        ];
    }
}

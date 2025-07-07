<?php

namespace ShahariarAhmad\CourierFraudCheckerBd\Services;

use Illuminate\Support\Facades\Http;
use ShahariarAhmad\CourierFraudCheckerBd\Helpers\CourierFraudCheckerHelper;

class SteadfastService
{
    protected string $email;
    protected string $password;

    public function __construct()
    {
        CourierFraudCheckerHelper::checkRequiredConfig([
            'courier-fraud-checker-bd.steadfast.user',
            'courier-fraud-checker-bd.steadfast.password',
        ]);

        $this->email = config('courier-fraud-checker-bd.steadfast.user');
        $this->password = config('courier-fraud-checker-bd.steadfast.password');
    }

    public function steadfast($phoneNumber)
    {
        CourierFraudCheckerHelper::validatePhoneNumber($phoneNumber);

        // Step 1: Fetch login page
        $response = Http::get('https://steadfast.com.bd/login');

        // Extract CSRF token
        preg_match('/<input type="hidden" name="_token" value="(.*?)"/', $response->body(), $matches);
        $token = $matches[1] ?? null;

        if (!$token) {
            return ['error' => 'CSRF token not found'];
        }

        // Convert CookieJar to array
        $rawCookies = $response->cookies();
        $cookiesArray = [];
        foreach ($rawCookies->toArray() as $cookie) {
            $cookiesArray[$cookie['Name']] = $cookie['Value'];
        }

        // Step 2: Log in
        $loginResponse = Http::withCookies($cookiesArray, 'steadfast.com.bd')
            ->asForm()
            ->post('https://steadfast.com.bd/login', [
                '_token' => $token,
                'email' => $this->email,
                'password' => $this->password,
            ]);

        if (!($loginResponse->successful() || $loginResponse->redirect())) {
            return ['error' => 'Login to Steadfast failed'];
        }

        // Rebuild cookies after login
        $loginCookiesArray = [];
        foreach ($loginResponse->cookies()->toArray() as $cookie) {
            $loginCookiesArray[$cookie['Name']] = $cookie['Value'];
        }

        // Step 3: Access fraud data
        $authResponse = Http::withCookies($loginCookiesArray, 'steadfast.com.bd')
            ->get("https://steadfast.com.bd/user/frauds/check/{$phoneNumber}");

        if (!$authResponse->successful()) {
            return ['error' => 'Failed to fetch fraud data from Steadfast'];
        }

        $object = $authResponse->collect()->toArray();

        $result = [
            'success' => $object['total_delivered'] ?? 0,
            'cancel' => $object['total_cancelled'] ?? 0,
            'total'  => ($object['total_delivered'] ?? 0) + ($object['total_cancelled'] ?? 0),
        ];

        // Step 4: Logout
        $logoutGET = Http::withCookies($loginCookiesArray, 'steadfast.com.bd')
            ->get('https://steadfast.com.bd/user/frauds/check');

        if ($logoutGET->successful()) {
            $html = $logoutGET->body();

            if (preg_match('/<meta name="csrf-token" content="(.*?)"/', $html, $matches)) {
                $csrfToken = $matches[1];

                Http::withCookies($loginCookiesArray, 'steadfast.com.bd')
                    ->asForm()
                    ->post('https://steadfast.com.bd/logout', [
                        '_token' => $csrfToken,
                    ]);
            }
        }

        return $result;
    }
}

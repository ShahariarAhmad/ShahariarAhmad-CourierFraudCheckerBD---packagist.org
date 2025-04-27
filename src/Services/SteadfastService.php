<?php

namespace ShahariarAhmad\CourierFraudCheckerBd\Services;
use Illuminate\Support\Facades\Http;
use ShahariarAhmad\CourierFraudCheckerBd\Helpers\CourierFraudCheckerHelper;

class SteadfastService
{
    public function __construct()
    {
        // Reusable check for required environment variables
        CourierFraudCheckerHelper::checkRequiredEnv(['STEADFAST_USER', 'STEADFAST_PASSWORD']);
    }
    public function steadfast($phoneNumber)
    {
        CourierFraudCheckerHelper::validatePhoneNumber($phoneNumber);
        $email = env('STEADFAST_USER');
        $password = env('STEADFAST_PASSWORD');

        // Step 1: Fetch login page
        $response = Http::get('https://steadfast.com.bd/login');

        // Extract CSRF token
        preg_match('/<input type="hidden" name="_token" value="(.*?)"/', $response->body(), $matches);
        $token = $matches[1] ?? null;

        if (!$token) {
            dd('CSRF token not found');
        }

        // 🔄 Convert CookieJar to associative array
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
                'email' => $email,
                'password' => $password
            ]);

        // Check if the login response was a redirect or successful
        if ($loginResponse->successful() || $loginResponse->redirect()) {
            // 🔄 Again, convert CookieJar after login
            $loginCookiesArray = [];
            foreach ($loginResponse->cookies()->toArray() as $cookie) {
                $loginCookiesArray[$cookie['Name']] = $cookie['Value'];
            }

            // Step 3: Access protected page
            $authResponse = Http::withCookies($loginCookiesArray, 'steadfast.com.bd')
                ->get('https://steadfast.com.bd/user/frauds/check/' . $phoneNumber);

            if ($authResponse->successful()) {
                $object = $authResponse->collect()->toArray();  // Only necessary for collections.
            }
        } else {
            return null;
        }

        $steadfast = [
            'success' => $object[0],
            'cancel' =>  $object[1],
            'total' => $object[0] + $object[1],
        ];

        $logoutGET = Http::withCookies($loginCookiesArray, 'steadfast.com.bd')
            ->get('https://steadfast.com.bd/user/frauds/check');

        // Ensure the HTML is not empty
        if ($logoutGET->successful()) {
            $html = $logoutGET->body();

            // Attempt to extract CSRF token
            if (preg_match('/<meta name="csrf-token" content="(.*?)"/', $html, $matches)) {
                $csrfToken = $matches[1];

                $logoutPost = Http::withCookies($loginCookiesArray, 'steadfast.com.bd')
                    ->asForm()
                    ->post('https://steadfast.com.bd/logout', [
                        '_token' => $csrfToken
                    ]);
            }
        }

        return $steadfast;
    }
}

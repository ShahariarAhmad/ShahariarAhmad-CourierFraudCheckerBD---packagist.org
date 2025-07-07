<?php

namespace ShahariarAhmad\CourierFraudCheckerBd\Helpers;

use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class CourierFraudCheckerHelper
{
    /**
     * Check that all required environment variables are set.
     *
     * @param array $requiredEnv
     * @throws InvalidArgumentException
     */
    public static function checkRequiredEnv(array $requiredEnv)
    {
        foreach ($requiredEnv as $envVar) {
            if (empty(env($envVar))) {
                throw new InvalidArgumentException("The environment variable $envVar is required but not set.");
            }
        }
    }

    /**
     * Validate the phone number to ensure it is a valid Bangladeshi number.
     *
     * @param string $phoneNumber
     * @throws InvalidArgumentException
     */
    // Validate if the phone number is a valid Bangladeshi phone number
    public static function validatePhoneNumber($phoneNumber)
    {
        $validator = Validator::make(
            ['phone' => $phoneNumber],
            [
                'phone' => [
                    'required',
                    'regex:/^01[3-9][0-9]{8}$/'
                ]
            ],
            [
                'phone.regex' => 'Invalid Bangladeshi phone number. Remember, you do not need to include the +88 prefix. Only use the local format (e.g., 01712345678).'
            ]
        );

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first('phone'));
        }
    }


    // CourierFraudCheckerHelper.php
    public static function checkRequiredConfig(array $requiredKeys)
    {
        foreach ($requiredKeys as $key) {
            if (empty(config($key))) {
                throw new InvalidArgumentException("The config key {$key} is required but not set.");
            }
        }
    }
}

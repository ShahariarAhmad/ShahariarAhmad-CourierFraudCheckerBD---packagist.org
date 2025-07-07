<?php

namespace ShahariarAhmad\CourierFraudCheckerBd;

use Illuminate\Support\ServiceProvider;
use ShahariarAhmad\CourierFraudCheckerBd\Services\SteadfastService;
use ShahariarAhmad\CourierFraudCheckerBd\Services\PathaoService;
use ShahariarAhmad\CourierFraudCheckerBd\Services\RedxService;

class CourierFraudCheckerBdServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publish the config file on vendor:publish
        $this->publishes([
            __DIR__ . '/../config/courier-fraud-checker-bd.php' => config_path('courier-fraud-checker-bd.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/courier-fraud-checker-bd.php', 'courier-fraud-checker-bd'
        );

        $this->app->singleton('courier-fraud-checker-bd', function ($app) {
            return new class($app) {
                protected $steadfastService;
                protected $pathaoService;
                protected $redxService;

                public function __construct($app)
                {
                    $this->steadfastService = $app->make(SteadfastService::class);
                    $this->pathaoService = $app->make(PathaoService::class);
                    $this->redxService = $app->make(RedxService::class);
                }

                public function check($phoneNumber)
                {
                    return [
                        'steadfast' => $this->steadfastService->steadfast($phoneNumber),
                        'pathao' => $this->pathaoService->pathao($phoneNumber),
                        'redx' => $this->redxService->getCustomerDeliveryStats($phoneNumber),
                    ];
                }
            };
        });
    }
}

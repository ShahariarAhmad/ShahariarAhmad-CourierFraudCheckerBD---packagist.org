<?php

namespace ShahariarAhmad\CourierFraudCheckerBd;

use Illuminate\Support\ServiceProvider;
use ShahariarAhmad\CourierFraudCheckerBd\Services\SteadfastService;
use ShahariarAhmad\CourierFraudCheckerBd\Services\PathaoService;
use ShahariarAhmad\CourierFraudCheckerBd\Facade\CourierFraudCheckerBdFacade;

class CourierFraudCheckerBdServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        // You can publish config files or views here if necessary
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        // Register the services
        $this->app->singleton(SteadfastService::class, function ($app) {
            return new SteadfastService();
        });

        $this->app->singleton(PathaoService::class, function ($app) {
            return new PathaoService();
        });

        // Register the main service as a single object to handle both services
        $this->app->singleton('courier-fraud-checker-bd', function ($app) {
            return new class($app) {
                protected $steadfastService;
                protected $pathaoService;

                public function __construct($app)
                {
                    $this->steadfastService = $app->make(SteadfastService::class);
                    $this->pathaoService = $app->make(PathaoService::class);
                }

                public function check($phoneNumber)
                {
                    // You can switch between which service to use based on your logic
                    $steadfastResult = $this->steadfastService->steadfast($phoneNumber);
                    $pathaoResult = $this->pathaoService->pathao($phoneNumber);

                    // Combine or return whichever result you need
                    return [
                        'steadfast' => $steadfastResult,
                        'pathao' => $pathaoResult
                    ];
                }
            };
        });
    }
}

<?php

namespace ShahariarAhmad\CourierFraudCheckerBd;

use Illuminate\Support\ServiceProvider;
use ShahariarAhmad\CourierFraudCheckerBd\Services\SteadfastService;
use ShahariarAhmad\CourierFraudCheckerBd\Services\PathaoService;
use ShahariarAhmad\CourierFraudCheckerBd\Services\RedxService;

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
      // In register() -> singleton

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
            $steadfastResult = $this->steadfastService->steadfast($phoneNumber);
            $pathaoResult = $this->pathaoService->pathao($phoneNumber);
            $redxResult = $this->redxService->getCustomerDeliveryStats($phoneNumber);

            return [
                'steadfast' => $steadfastResult,
                'pathao' => $pathaoResult,
                'redx' => $redxResult,
            ];
        }
    };
});

    }
}

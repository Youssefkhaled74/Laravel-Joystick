<?php

namespace App\Providers;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Illuminate\Support\ServiceProvider;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase', function () {
            $serviceAccount = ServiceAccount::fromJsonFile(config('firebase.credentials'));
            return (new Factory)
                ->withServiceAccount($serviceAccount)
                ->create();
        });
    }

    public function boot()
    {
        //
    }
}
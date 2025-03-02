<?php

namespace App\Providers;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use App\Channels\FirebaseChannel;
use Psr\Http\Message\UriInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register()
    {
        // Bind the URI interface to a concrete implementation
        $this->app->bind(UriInterface::class, function () {
            // Return a default, empty Uri.
            return new Uri('');
        });
        $this->app->bind(ClientInterface::class, Client::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notification::extend('firebase', function ($app) {
            return new FirebaseChannel();
        });
    }
}

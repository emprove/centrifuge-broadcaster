<?php

namespace Emprove\Centrifugo;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;

class CentrifugoServiceProvider extends ServiceProvider
{
    /**
     * Add centrifugo broadcaster.
     *
     * @param BroadcastManager $broadcastManager
     */
    public function boot(BroadcastManager $broadcastManager)
    {
        $broadcastManager->extend('centrifugo', function ($app) {
            return new CentrifugoBroadcaster($app->make('centrifugo'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('centrifugo', function ($app) {
            $config = $app->make('config')->get('broadcasting.connections.centrifugo');
            $http   = new Client();

            return new Centrifugo($config, $http);
        });

        $this->app->alias('centrifugo', 'Emprove\Centrifugo\Centrifugo');
        $this->app->alias('centrifugo', 'Emprove\Centrifugo\Contracts\Centrifugo');
    }
}

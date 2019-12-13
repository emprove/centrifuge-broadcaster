<?php

namespace Emprove\Centrifugo\Test;

use Orchestra\Testbench\TestCase as Orchestra;
use Emprove\Centrifugo\Centrifugo;
use Emprove\Centrifugo\CentrifugoServiceProvider;

class TestCase extends Orchestra
{
    /**
     * @var Centrifugo
     */
    protected $centrifugo;

    public function setUp()
    {
        parent::setUp();

        $this->centrifugo = $this->app->make('centrifugo');
    }

    protected function getPackageProviders($app)
    {
        return [
            CentrifugoServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('broadcasting.default', 'centrifugo');
        $app['config']->set('broadcasting.connections.centrifugo', [
            'driver' => 'centrifugo',
            'secret' => 'f95bf295-bee6-4259-8912-0a58f4ecd30e',
            'url'    => 'http://localhost:8000',
        ]);
    }
}

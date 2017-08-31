<?php

namespace Remp\LaravelSso\Providers;

use Remp\LaravelSso\Contracts\Jwt\Guard;
use Remp\LaravelSso\Contracts\SsoContract;
use Remp\LaravelSso\Contracts\Remp\Sso;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class SsoServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $path = realpath(__DIR__.'/../../config/services.php');
        $config = $this->app['config']->get('services', []);
        $this->app['config']->set('services', array_merge(require $path, $config));

        Auth::extend('jwt', function ($app, $name, array $config) {
            return $app->make(Guard::class);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(SsoContract::class, function($app){
            $client = new Client([
                'base_uri' => $app['config']->get('services.remp.sso.web_addr'),
            ]);
            return new Sso($client);
        });
    }

    public function provides()
    {
        return [Sso::class];
    }
}

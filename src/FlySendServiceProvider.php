<?php

namespace FlySend\Laravel;

use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;

class FlySendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/flysend.php', 'flysend');

        $this->app->singleton(FlySendApiClient::class, function ($app) {
            return new FlySendApiClient(
                $app['config']['flysend.api_key'] ?? '',
                $app['config']['flysend.endpoint'] ?? 'https://api.flysend.co',
            );
        });

        $this->app->singleton(FlySend::class, function ($app) {
            return new FlySend($app->make(FlySendApiClient::class));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/flysend.php' => config_path('flysend.php'),
        ], 'flysend-config');

        $this->app->afterResolving('mail.manager', function (MailManager $mailManager) {
            $mailManager->extend('flysend', function (array $config) {
                $apiKey = $config['api_key'] ?? $this->app['config']['flysend.api_key'];
                $endpoint = $config['endpoint'] ?? $this->app['config']['flysend.endpoint'];

                return new FlySendTransport(
                    new FlySendApiClient($apiKey, $endpoint)
                );
            });
        });
    }
}

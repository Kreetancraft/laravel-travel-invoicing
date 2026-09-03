<?php

declare(strict_types=1);

namespace Kreetancraft\TravelInvoicing\Tests;

use Flux\FluxServiceProvider;
use Kreetancraft\TravelInvoicing\Providers\TravelInvoicingServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        $providers = [
            LivewireServiceProvider::class,
            TravelInvoicingServiceProvider::class,
        ];

        if (class_exists(FluxServiceProvider::class)) {
            $providers[] = FluxServiceProvider::class;
        }

        if (class_exists(PermissionServiceProvider::class)) {
            $providers[] = PermissionServiceProvider::class;
        }

        return $providers;
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('travel-invoicing.routes.register_admin', true);
        $app['config']->set('travel-invoicing.routes.register_public', true);
        $app['config']->set('travel-invoicing.routes.register_api', true);
        $app['config']->set('travel-invoicing.routes.middleware', ['web']);
        $app['config']->set('travel-invoicing.routes.public_middleware', ['web']);

        $app['config']->set('view.paths', [
            __DIR__.'/fixtures/views',
            __DIR__.'/../resources/views',
            resource_path('views'),
        ]);

        $app['config']->set('travel-invoicing.layouts.admin', 'fixtures-layout');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}

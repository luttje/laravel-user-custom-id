<?php

namespace Luttje\UserCustomId\Tests;

use Luttje\UserCustomId\UserCustomIdServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            UserCustomIdServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.env', env('APP_ENV', 'testing'));
        $app['config']->set('app.debug', env('APP_DEBUG', true));
        $app['config']->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYfiBfCuSThJ5SK8=');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations(['--database' => 'testing']);

        $this->loadMigrationsFrom([
            '--database' => 'testing',
            '--path' => realpath(__DIR__.'/../database/migrations'),
        ]);

        // Test only migrations
        $this->loadMigrationsFrom([
            '--database' => 'testing',
            '--path' => realpath(__DIR__.'/Fixtures/database/migrations'),
        ]);

        $this->artisan('migrate', ['--database' => 'testing'])
            ->run();
    }
}

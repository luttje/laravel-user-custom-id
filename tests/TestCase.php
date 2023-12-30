<?php

namespace Luttje\UserCustomId\Tests;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;
use Luttje\UserCustomId\FormatChunks\Literal;
use Luttje\UserCustomId\Tests\Fixtures\Models\User;
use Luttje\UserCustomId\UserCustomIdServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string<\Illuminate\Support\ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return [
            UserCustomIdServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        tap($app['config'], function (Repository $config) {
            $config->set('database.default', 'testing');
            $config->set('database.connections.testing', [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ]);

            $config->set('app.env', env('APP_ENV', 'testing'));
            $config->set('app.debug', env('APP_DEBUG', true));
            $config->set('app.key', 'base64:Hupx3yAySikrM2/edkZQNQHslgDWYfiBfCuSThJ5SK8=');
        });
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations();

        $this->loadMigrationsFrom(realpath(__DIR__.'/../database/migrations'));

        // Test only migrations
        $this->loadMigrationsFrom(realpath(__DIR__.'/Fixtures/Database/migrations'));
    }

    protected function createCustomId(
        Model $owner,
        Model|string $targetOrClass,
        string $format,
        ?string $targetAttribute = null,
        ?FormatChunkCollection $lastValueChunks = null,
    ) {
        $targetClass = $targetOrClass instanceof Model
            ? $targetOrClass->getMorphClass()
            : $targetOrClass;

        return UserCustomId::createFormat($targetClass, $owner, $format, $targetAttribute, $lastValueChunks);
    }

    protected function createOwnerWithCustomId(
        Model|string $targetOrClass,
        string $format,
        ?string $targetAttribute = null,
        ?FormatChunkCollection $lastValueChunks = null,
    ) {
        $owner = User::factory()->create();
        $this->createCustomId($owner, $targetOrClass, $format, $targetAttribute, $lastValueChunks);

        return $owner;
    }

    protected function makeLiteral(string $value)
    {
        $literal = new Literal();
        $literal->setValue($value);

        return $literal;
    }
}

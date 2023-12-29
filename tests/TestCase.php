<?php

namespace Luttje\UserCustomId\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;
use Luttje\UserCustomId\FormatChunks\Literal;
use Luttje\UserCustomId\Tests\Fixtures\Models\User;
use Luttje\UserCustomId\UserCustomIdServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Luttje\\UserCustomId\\Tests\\Fixtures\\Database\\Factories\\'.class_basename($modelName).'Factory',
        );
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
            '--path' => realpath(__DIR__.'/../Database/migrations'),
        ]);

        // Test only migrations
        $this->loadMigrationsFrom([
            '--database' => 'testing',
            '--path' => realpath(__DIR__.'/Fixtures/Database/migrations'),
        ]);

        $this->artisan('migrate', ['--database' => 'testing'])
            ->run();
    }

    protected function createCustomId(
        Model $owner,
        Model|string $targetOrClass,
        string $format,
        ?string $targetAttribute = null,
        ?FormatChunkCollection $lastValueChunks = null,
    ) {
        return UserCustomId::create($targetOrClass, $owner, $format, $targetAttribute, $lastValueChunks);
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

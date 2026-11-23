<?php

namespace Luttje\UserCustomId\Tests\Unit;

use Illuminate\Support\ServiceProvider;
use Luttje\UserCustomId\Tests\TestCase;
use Luttje\UserCustomId\UserCustomIdServiceProvider;

final class ServiceProviderTest extends TestCase
{
    public function test_migration_can_be_published(): void
    {
        $paths = ServiceProvider::pathsToPublish(
            UserCustomIdServiceProvider::class,
            'user-custom-id-migrations'
        );

        $this->assertCount(1, $paths);

        $source = array_key_first($paths);
        $destination = $paths[$source];

        // Publishing silently does nothing when the source is missing.
        $this->assertFileExists($source);
        $this->assertStringEndsWith('_create_user_custom_ids_table.php', $destination);
        $this->assertStringStartsWith(database_path('migrations'), $destination);
    }

    public function test_package_only_ships_its_own_migrations(): void
    {
        $shipped = array_map('basename', glob(__DIR__.'/../../database/migrations/*'));

        $this->assertSame(['create_user_custom_ids_table.php'], $shipped);
    }
}

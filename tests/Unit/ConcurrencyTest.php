<?php

namespace Luttje\UserCustomId\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Luttje\UserCustomId\Tests\Fixtures\Models\User;
use Luttje\UserCustomId\Tests\TestCase;

/**
 * Lets a test look at (or interfere with) the moment an id is generated.
 */
class ProbeChunk extends FormatChunk
{
    public static ?\Closure $callback = null;

    public static function getChunkId(): string
    {
        return 'probe';
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        return (self::$callback)();
    }
}

final class ConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        UserCustomId::registerChunkType(ProbeChunk::class);
    }

    public function test_id_is_generated_inside_a_transaction(): void
    {
        $owner = User::factory()->create();
        UserCustomId::createFormat(Product::class, $owner, '{increment}{probe}', 'custom_id');

        $levelOutside = DB::transactionLevel();
        $levelInside = null;

        ProbeChunk::$callback = function () use (&$levelInside) {
            $levelInside = DB::transactionLevel();

            return '';
        };

        UserCustomId::generateFor(new Product, $owner);

        $this->assertSame($levelOutside + 1, $levelInside);
        $this->assertSame($levelOutside, DB::transactionLevel());
    }

    public function test_counter_is_not_advanced_when_generating_fails(): void
    {
        $owner = User::factory()->create();
        UserCustomId::createFormat(Product::class, $owner, '{increment}{probe}', 'custom_id');

        ProbeChunk::$callback = fn () => '';

        $this->assertSame('1', UserCustomId::generateFor(new Product, $owner));

        ProbeChunk::$callback = fn () => throw new \RuntimeException('Something went wrong.');

        $this->assertThrows(
            fn () => UserCustomId::generateFor(new Product, $owner),
            \RuntimeException::class
        );

        ProbeChunk::$callback = fn () => '';

        $this->assertSame('2', UserCustomId::generateFor(new Product, $owner));
    }

    public function test_counter_is_rolled_back_with_surrounding_transaction(): void
    {
        $owner = User::factory()->create();
        UserCustomId::createFormat(Product::class, $owner, 'INV-{increment}', 'custom_id');

        DB::beginTransaction();

        $rolledBack = UserCustomId::generateFor(new Product, $owner);

        DB::rollBack();

        // The product with INV-1 was never stored, so the number is free again.
        $afterwards = [
            UserCustomId::generateFor(new Product, $owner),
            UserCustomId::generateFor(new Product, $owner),
        ];

        $this->assertSame('INV-1', $rolledBack);
        $this->assertSame(['INV-1', 'INV-2'], $afterwards);
    }

    public function test_nothing_is_generated_without_a_format(): void
    {
        $owner = User::factory()->create();
        $product = new Product;

        $this->assertNull(UserCustomId::generateFor($product, $owner));
        $this->assertNull($product->custom_id);
    }
}

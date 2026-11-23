<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\Tests\Fixtures\MockRandomChunk;
use Luttje\UserCustomId\Tests\Fixtures\Models\Category;

final class RandomTest extends FormatChunkTestCase
{
    public function test_random(): void
    {
        $chunk = $this->getChunk('random', [10, 'A']);

        $this->assertEquals('AAAAAAAAAA', $this->getNextValue($chunk));
        $this->assertEquals('AAAAAAAAAA', $this->getNextValue($chunk));
        $this->assertEquals('AAAAAAAAAA', $this->getNextValue($chunk));
    }

    public function test_random_only_uses_given_characters(): void
    {
        $chunk = $this->getChunk('random', [255, 'xyz']);

        $first = $this->getNextValue($chunk);
        $second = $this->getNextValue($chunk);

        $this->assertMatchesRegularExpression('/^[xyz]{255}$/', $first);
        $this->assertMatchesRegularExpression('/^[xyz]{255}$/', $second);

        // 3^255 possibilities, these will not be the same by chance.
        $this->assertNotSame($first, $second);
    }

    public function test_random_does_not_use_predictable_generator(): void
    {
        $chunk = $this->getChunk('random', [32]);

        // Seeding only affects rand() and mt_rand(), so the same seed must
        // not result in the same "random" characters.
        // (Created up front, so nothing else happens between seeding and generating.)
        $model = new Category;

        mt_srand(42);
        $first = $chunk->getNextValue($model, $model);

        mt_srand(42);
        $second = $chunk->getNextValue($model, $model);

        // Don't leave the generator seeded for the other tests.
        mt_srand();

        $this->assertNotSame($first, $second);
    }

    public function test_random_with_custom_characters(): void
    {
        // Mock random function
        MockRandomChunk::$sequence = [0, 1, 2, 3, 4];
        UserCustomId::registerChunkType(MockRandomChunk::class);

        $chunk = $this->getChunk('random', [5, 'AbCdE']);

        $this->assertEquals('AbCdE', $this->getNextValue($chunk));
    }

    public function test_random_with_custom_characters_and_length(): void
    {
        // Mock random function
        MockRandomChunk::$sequence = [0, 1, 2, 3, 4];
        UserCustomId::registerChunkType(MockRandomChunk::class);

        $chunk = $this->getChunk('random', [10, 'AbCdE']);

        $this->assertEquals('AbCdEAbCdE', $this->getNextValue($chunk));
    }
}

<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\Tests\Fixtures\MockRandomChunk;

final class RandomTest extends FormatChunkTestCase
{
    public function test_random(): void
    {
        $chunk = $this->getChunk('random', [10, 'A']);

        $this->assertEquals('AAAAAAAAAA', $this->getNextValue($chunk));
        $this->assertEquals('AAAAAAAAAA', $this->getNextValue($chunk));
        $this->assertEquals('AAAAAAAAAA', $this->getNextValue($chunk));
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

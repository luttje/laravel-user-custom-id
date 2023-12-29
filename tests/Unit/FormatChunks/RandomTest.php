<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\Tests\Fixtures\MockRandomChunk;

final class RandomTest extends FormatChunkTestCase
{
    public function testRandom(): void
    {
        $chunk = $this->getChunk('random', [10, 'A']);

        $this->assertEquals('AAAAAAAAAA', $this->getNextValue($chunk));
        $this->assertEquals('AAAAAAAAAA', $this->getNextValue($chunk));
        $this->assertEquals('AAAAAAAAAA', $this->getNextValue($chunk));
    }

    public function testRandomWithCustomCharacters(): void
    {
        // Mock random function
        MockRandomChunk::$sequence = [0, 1, 2, 3, 4];
        UserCustomId::registerChunkType(MockRandomChunk::class);

        $chunk = $this->getChunk('random', [5, 'AbCdE']);

        $this->assertEquals('AbCdE', $this->getNextValue($chunk));
    }

    public function testRandomWithCustomCharactersAndLength(): void
    {
        // Mock random function
        MockRandomChunk::$sequence = [0, 1, 2, 3, 4];
        UserCustomId::registerChunkType(MockRandomChunk::class);

        $chunk = $this->getChunk('random', [10, 'AbCdE']);

        $this->assertEquals('AbCdEAbCdE', $this->getNextValue($chunk));
    }
}

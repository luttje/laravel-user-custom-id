<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

final class IncrementTest extends FormatChunkTestCase
{
    public function testIncrement(): void
    {
        $chunk = $this->getChunk('increment', [1]);

        $this->assertEquals('1', $this->getNextValue($chunk));

        $chunk = $this->getChunk('increment', [1], 1);
        $this->assertEquals('2', $this->getNextValue($chunk));

        $chunk = $this->getChunk('increment', [1], 10);
        $this->assertEquals('11', $this->getNextValue($chunk));

        $chunk = $this->getChunk('increment', [1], -1);
        $this->assertEquals('0', $this->getNextValue($chunk));
    }

    public function testIncrementDouble(): void
    {
        $chunk = $this->getChunk('increment', [0.1]);

        $this->assertEquals('0.1', $this->getNextValue($chunk));

        $chunk = $this->getChunk('increment', [0.1], 0.1);

        $this->assertEquals('0.2', $this->getNextValue($chunk));

        $chunk = $this->getChunk('increment', [0.1], 0.9);

        $this->assertEquals('1', $this->getNextValue($chunk));

        $chunk = $this->getChunk('increment', [0.1], -0.1);

        $this->assertEquals('0', $this->getNextValue($chunk));
    }
}

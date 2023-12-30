<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\FormatChunks\Increment;

final class FormatChunkTest extends FormatChunkTestCase
{
    public function testGetDefaultParameters(): void
    {
        $defaultParameters = [1, 0, '-'];

        $this->assertEquals($defaultParameters, Increment::getDefaultParameters());
    }

    public function testCannotMissRequiredParameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getChunk('attribute', []);
    }

    public function testCannotGiveParameterOfWrongType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getChunk('random', ['not a number']);
    }

    public function testCanConvertChunkToString(): void
    {
        $chunk = $this->getChunk('increment', [42, 3, '-'], 1234567);

        $this->assertEquals('{increment:42:3:-}', (string) $chunk);
    }
}

<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\FormatChunks\Increment;

final class FormatChunkTest extends FormatChunkTestCase
{
    public function test_get_default_parameters(): void
    {
        $defaultParameters = [1, 0, '-'];

        $this->assertEquals($defaultParameters, Increment::getDefaultParameters());
    }

    public function test_cannot_miss_required_parameters(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getChunk('attribute', []);
    }

    public function test_cannot_give_parameter_of_wrong_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->getChunk('random', ['not a number']);
    }

    public function test_can_convert_chunk_to_string(): void
    {
        $chunk = $this->getChunk('increment', [42, 3, '-'], 1234567);

        $this->assertEquals('{increment:42:3:-}', (string) $chunk);
    }
}

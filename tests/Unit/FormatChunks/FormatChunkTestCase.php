<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\Tests\TestCase;

abstract class FormatChunkTestCase extends TestCase
{
    protected function getChunk(string $id, array $parameters = [], mixed $value = null): FormatChunk
    {
        return FormatChunk::fromArray([
            'id' => $id,
            'parameters' => $parameters,
            'value' => $value,
        ]);
    }

    protected function getNextValue(FormatChunk $chunk)
    {
        // mock a target and owner model (not needed for Random)
        /** @var \Illuminate\Database\Eloquent\Model */
        $target = $this->getMockBuilder(\Illuminate\Database\Eloquent\Model::class)
            ->getMock();

        return $chunk->getNextValue($target, $target);
    }
}

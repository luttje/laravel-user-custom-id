<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

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

    protected function getNextValue(FormatChunk $chunk, array $mockModelMethods = [])
    {
        // mock a target and owner model (not needed for Random)
        /** @var MockObject&Model */
        $target = $this->getMockBuilder(Model::class)
            ->onlyMethods($mockModelMethods)
            ->getMock();

        return $chunk->getNextValue($target, $target);
    }
}

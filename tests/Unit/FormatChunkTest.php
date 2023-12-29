<?php

namespace Luttje\UserCustomId\Tests\Unit;

use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\Tests\Fixtures\MockRandomChunk;
use Luttje\UserCustomId\Tests\TestCase;

final class FormatChunkTest extends TestCase
{
    private function getChunk(string $id, array $parameters = [], mixed $value = null): \Luttje\UserCustomId\FormatChunks\FormatChunk
    {
        return \Luttje\UserCustomId\FormatChunks\FormatChunk::fromArray([
            'id' => $id,
            'parameters' => $parameters,
            'value' => $value,
        ]);
    }

    public function test_increment(): void
    {
        $chunk = $this->getChunk('increment', ['amount' => 1]);

        $this->assertEquals('1', $chunk->getNextValue());

        $chunk = $this->getChunk('increment', ['amount' => 1], 1);
        $this->assertEquals('2', $chunk->getNextValue());

        $chunk = $this->getChunk('increment', ['amount' => 1], 10);
        $this->assertEquals('11', $chunk->getNextValue());

        $chunk = $this->getChunk('increment', ['amount' => 1], -1);
        $this->assertEquals('0', $chunk->getNextValue());
    }

    public function test_increment_double(): void
    {
        $chunk = $this->getChunk('increment', ['amount' => 0.1]);

        $this->assertEquals('0.1', $chunk->getNextValue());

        $chunk = $this->getChunk('increment', ['amount' => 0.1], 0.1);

        $this->assertEquals('0.2', $chunk->getNextValue());

        $chunk = $this->getChunk('increment', ['amount' => 0.1], 0.9);

        $this->assertEquals('1', $chunk->getNextValue());

        $chunk = $this->getChunk('increment', ['amount' => 0.1], -0.1);

        $this->assertEquals('0', $chunk->getNextValue());
    }

    public function test_random(): void
    {
        $chunk = $this->getChunk('random', ['length' => 10, 'characters' => 'A']);

        $this->assertEquals('AAAAAAAAAA', $chunk->getNextValue());
        $this->assertEquals('AAAAAAAAAA', $chunk->getNextValue());
        $this->assertEquals('AAAAAAAAAA', $chunk->getNextValue());
    }

    public function test_random_with_custom_characters(): void
    {
        // Mock random function
        MockRandomChunk::$sequence = [0, 1, 2, 3, 4];
        UserCustomId::registerChunkType(MockRandomChunk::class);

        $chunk = $this->getChunk('random', ['length' => 5, 'characters' => 'AbCdE']);

        $this->assertEquals('AbCdE', $chunk->getNextValue());
    }

    public function test_random_with_custom_characters_and_length(): void
    {
        // Mock random function
        MockRandomChunk::$sequence = [0, 1, 2, 3, 4];
        UserCustomId::registerChunkType(MockRandomChunk::class);

        $chunk = $this->getChunk('random', ['length' => 10, 'characters' => 'AbCdE']);

        $this->assertEquals('AbCdEAbCdE', $chunk->getNextValue());
    }
}

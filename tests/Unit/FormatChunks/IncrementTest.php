<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;
use Luttje\UserCustomId\Tests\Fixtures\Models\Category;

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

    public function testIncrementGrouping(): void
    {
        $chunk = $this->getChunk('increment', [1, 3, '-'], 1234566);

        $this->assertEquals('123-456-7', $this->getNextValue($chunk));
    }

    public function testIncrementGroupingForInstance(): void
    {
        $lastValueChunks = new FormatChunkCollection([
            $this->getChunk('increment', [1, 3, '-'], 1234567),
        ]);
        $owner = $this->createOwnerWithCustomId(Category::class, '{increment:1:3:-}', 'id', $lastValueChunks);

        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $result = UserCustomId::generateFor($category, $owner);

        $this->assertEquals('123-456-8', $result);
    }
}

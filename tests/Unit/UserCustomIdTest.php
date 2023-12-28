<?php

namespace Luttje\UserCustomId\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\FormatChunks\Literal;
use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Luttje\UserCustomId\Tests\TestCase;
use Orchestra\Testbench\Factories\UserFactory;

final class UserCustomIdTest extends TestCase
{
    private function createOwnerWithCustomId(
        Model|string $targetOrClass,
        string $format,
        ?string $targetAttribute = null,
        ?array $lastValueChunks = null,
    )
    {
        $owner = UserFactory::new()->create();
        UserCustomId::create($targetOrClass, $owner, $format, $targetAttribute, $lastValueChunks);

        return $owner;
    }

    private function makeLiteral(string $value)
    {
        $literal = new Literal();
        $literal->setValue($value);

        return $literal;
    }

    private function makeChunk(string $chunkId, mixed $value)
    {
        $chunkType = UserCustomId::getChunkType($chunkId);

        /** @var FormatChunk */
        $chunk = new $chunkType();
        $chunk->setValue($value);

        return $chunk;
    }

    public function testGenerateSimpleIncrement()
    {
        $format = 'prefix-{increment}SUFFIX';
        $lastValueChunks = [
            //'prefix-123455SUFFIX';
            $this->makeLiteral('prefix-'),
            $this->makeChunk('increment', 123455),
            $this->makeLiteral('SUFFIX'),
        ];
        $expected = 'prefix-123456SUFFIX';

        $chunks = UserCustomId::generate($format, $lastValueChunks);
        $result = UserCustomId::convertToString($chunks);

        $this->assertEquals($expected, $result);
    }

    public function testGenerateForUser()
    {
        $format = 'prefix-{increment}SUFFIX';
        $expected = 'prefix-1SUFFIX';

        $owner = $this->createOwnerWithCustomId(Category::class, $format, 'custom_id');

        $result = UserCustomId::generateFor(Category::class, $owner);

        $this->assertEquals($expected, $result);
    }
}

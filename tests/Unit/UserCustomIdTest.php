<?php

namespace Luttje\UserCustomId\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\FormatChunks\Literal;
use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
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

    public function testGenerateForClassType()
    {
        $format = 'prefix-{increment}SUFFIX';
        $expected = 'prefix-1SUFFIX';

        $owner = $this->createOwnerWithCustomId(Category::class, $format, 'custom_id');

        $this->assertDatabaseHas('user_custom_ids', [
            'target_type' => Category::class,
            'target_attribute' => 'custom_id',
            'owner_id' => $owner->id,
        ]);

        $result = UserCustomId::generateFor(Category::class, $owner);

        $this->assertEquals($expected, $result);
    }

    public function testGenerateForClassInstance()
    {
        $format = 'prefix-{increment}SUFFIX';
        $expected = 'prefix-1SUFFIX';

        $owner = $this->createOwnerWithCustomId(Category::class, $format, 'custom_id');

        $category = new Category([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category.',
            'owner_id' => $owner->id,
        ]);

        UserCustomId::generateFor($category, $owner);

        $category->save();

        $result = $category->custom_id;

        $this->assertEquals($expected, $result);

        $ownerCustomIdExists = \Luttje\UserCustomId\UserCustomId::latest()->first();

        $this->assertNotNull($ownerCustomIdExists);

        $lastId = $ownerCustomIdExists->last_target_custom_id;

        $this->assertNotNull($lastId);

        $this->assertEquals($expected, UserCustomId::convertToString($lastId));
    }
}

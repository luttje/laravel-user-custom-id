<?php

namespace Luttje\UserCustomId\Tests\Unit;

use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;
use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Luttje\UserCustomId\Tests\TestCase;

final class UserCustomIdTest extends TestCase
{
    private function makeChunk(string $id, mixed $value)
    {
        $chunkType = UserCustomId::getChunkType($id);

        /** @var FormatChunk */
        $chunk = new $chunkType();
        $chunk->setValue($value);

        return $chunk;
    }

    public function testGenerateSimpleIncrement()
    {
        $format = 'prefix-{increment}SUFFIX';
        $lastValueChunks = new FormatChunkCollection([
            //'prefix-123455SUFFIX';
            $this->makeLiteral('prefix-'),
            $this->makeChunk('increment', 123455),
            $this->makeLiteral('SUFFIX'),
        ]);
        $expected = 'prefix-123456SUFFIX';

        $owner = $this->createOwnerWithCustomId(Category::class, $format, 'id', $lastValueChunks);

        $category = new Category([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category.',
            'owner_id' => $owner->id,
        ]);

        $result = UserCustomId::generateFor($category, $owner);

        $this->assertEquals($expected, $result);
    }

    public function testGenerateForClassType()
    {
        $format = 'prefix-{increment}SUFFIX';
        $expected = 'prefix-1SUFFIX';

        $owner = $this->createOwnerWithCustomId(Category::class, $format, 'id');

        $this->assertDatabaseHas('user_custom_ids', [
            'target_type' => Category::class,
            'target_attribute' => 'id',
            'owner_id' => $owner->id,
        ]);

        $category = new Category([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category.',
            'owner_id' => $owner->id,
        ]);

        $result = UserCustomId::generateFor($category, $owner);

        $this->assertEquals($expected, $result);
    }

    public function testGenerateForClassInstance()
    {
        $format = 'prefix-{increment}SUFFIX';
        $expected = 'prefix-1SUFFIX';

        $owner = $this->createOwnerWithCustomId(Category::class, $format, 'id');

        $category = new Category([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category.',
            'owner_id' => $owner->id,
        ]);

        // Not needed for Category, as it implements HasUserCustomId with the WithUserCustomId trait.
        // That will automatically generate a custom id for the model based on the owning user.
        // UserCustomId::generateFor($category, $owner);

        $category->save();

        $result = $category->id;

        $this->assertEquals($expected, $result);

        $ownerCustomIdExists = \Luttje\UserCustomId\UserCustomId::latest()->first();

        $this->assertNotNull($ownerCustomIdExists);

        $lastId = $ownerCustomIdExists->last_target_custom_id;

        $this->assertNotNull($lastId);

        $this->assertEquals($expected, strval($lastId));
    }

    public function testGenerateForClassInstanceWithLastValueChunks()
    {
        $format = 'prefix-{increment}SUFFIX';
        $lastValueChunks = new FormatChunkCollection([
            $this->makeLiteral('prefix-'),
            $this->makeChunk('increment', 123455),
            $this->makeLiteral('SUFFIX'),
        ]);
        $expected = 'prefix-123456SUFFIX';

        $owner = $this->createOwnerWithCustomId(Category::class, $format, 'id', $lastValueChunks);

        $category = new Category([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category.',
            'owner_id' => $owner->id,
        ]);

        $category->save();

        $result = $category->id;

        $this->assertEquals($expected, $result);

        $ownerCustomIdExists = \Luttje\UserCustomId\UserCustomId::latest()->first();

        $this->assertNotNull($ownerCustomIdExists);

        $lastId = $ownerCustomIdExists->last_target_custom_id;

        $this->assertNotNull($lastId);

        $this->assertEquals($expected, strval($lastId));
    }

    public function testGenerateForClassInstanceIntoIdAttribute()
    {
        $format = 'prefix-{increment}SUFFIX';
        $expected = 'prefix-1SUFFIX';

        $owner = $this->createOwnerWithCustomId(Product::class, $format, 'custom_id');

        $product = new Product([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'This is a test product.',
        ]);

        UserCustomId::generateFor($product, $owner);

        $product->save();

        $result = $product->custom_id;

        $this->assertEquals($expected, $result);

        $ownerCustomIdExists = \Luttje\UserCustomId\UserCustomId::latest()->first();

        $this->assertNotNull($ownerCustomIdExists);

        $lastId = $ownerCustomIdExists->last_target_custom_id;

        $this->assertNotNull($lastId);

        $this->assertEquals($expected, strval($lastId));

        $this->assertDatabaseHas('products', [
            'custom_id' => $expected,
        ]);
    }

    public function testGenerateForClassInstanceFailsWithoutOwner()
    {
        $format = 'prefix-{increment}SUFFIX';

        $this->createOwnerWithCustomId(Category::class, $format, 'custom_id');

        $category = new Category([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category.',
        ]);

        // Expect an exception to be thrown.
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot create a custom id for a model without an owner. Did you forget to implement the getOwner() method?');

        $category->save();
    }

    public function testGenerateForClassInstanceWithForeignId()
    {
        $format = 'prefix-{increment}SUFFIX';
        $expected = 'prefix-1SUFFIX';

        $owner = $this->createOwnerWithCustomId(Category::class, $format, 'id');
        $this->createCustomId($owner, Product::class, $format, 'custom_id');

        $category = new Category([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'This is a test category.',
            'owner_id' => $owner->id,
        ]);

        $category->save();

        $result = $category->id;

        $this->assertEquals($expected, $result);

        $this->assertEmpty($category->products);

        // Insert a product with the category id as foreign key.
        $product = new Product([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'This is a test product.',
            'category_id' => $category->id,
        ]);

        UserCustomId::generateFor($product, $owner);

        $product->save();

        $category->refresh();

        $this->assertTrue($category->products->contains($product));
    }
}

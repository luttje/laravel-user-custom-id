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

        $this->assertEquals($owner->id, $ownerCustomIdExists->owner->id);

        $lastId = $ownerCustomIdExists->last_target_custom_id;

        $this->assertNotNull($lastId);

        $this->assertEquals($expected, strval($lastId));
    }

    public function testGenerateForClassInstanceDifferentOwners()
    {
        // In a situation where multiple owners have the same format AND the same target type and attribute,
        // If that attribute is unique, you will get a duplicate key error. In that situation you must ensure
        // that you (or the user) adds some kind of unique identifier to the format, like the owner id.
        // You will probably just not want to allow users to customize the primary key or unique attributes.
        // If you really want to allow that, make sure the model also has an owner foreign key and the id
        // is unique per owner ($table->unique(['custom_id', 'owner_id']);
        $format = 'prefix-{increment}SUFFIX';
        $expected = 'prefix-1SUFFIX';

        $ownerA = $this->createOwnerWithCustomId(Product::class, $format, 'custom_id');
        // Even if they have the same format it should work
        $ownerB = $this->createOwnerWithCustomId(Product::class, $format, 'custom_id');

        $productA = new Product([
            'name' => 'Test Product A',
            'slug' => 'test-product-a',
            'description' => 'This is a test product.',
            'owner_id' => $ownerA->custom_id,
        ]);

        UserCustomId::generateFor($productA, $ownerA);

        $productA->save();

        $productB = new Product([
            'name' => 'Test Category B',
            'slug' => 'test-product-b',
            'description' => 'This is a test product.',
            'owner_id' => $ownerB->custom_id,
        ]);

        UserCustomId::generateFor($productB, $ownerB);

        $productB->save();

        $resultA = $productA->custom_id;
        $resultB = $productB->custom_id;

        $this->assertEquals($expected, $resultA);
        $this->assertEquals($expected, $resultB);

        $productB2 = new Product([
            'name' => 'Test Category B',
            'slug' => 'test-product-b2',
            'description' => 'This is a test product.',
            'owner_id' => $ownerB->custom_id,
        ]);

        UserCustomId::generateFor($productB2, $ownerB);

        $productB2->save();

        // Get the last id for owner A and check that it's not the same as the last id for owner B.
        // We don't want one user to influence the custom id of another user.
        $ownerACustomIdExists = \Luttje\UserCustomId\UserCustomId::where('owner_id', $ownerA->id)->latest()->first();
        $ownerBCustomIdExists = \Luttje\UserCustomId\UserCustomId::where('owner_id', $ownerB->id)->latest()->first();

        $this->assertNotNull($ownerACustomIdExists);
        $this->assertNotNull($ownerBCustomIdExists);

        $lastIdA = $ownerACustomIdExists->last_target_custom_id;
        $lastIdB = $ownerBCustomIdExists->last_target_custom_id;

        $this->assertNotNull($lastIdA);
        $this->assertNotNull($lastIdB);

        $this->assertEquals($expected, strval($lastIdA));
        $this->assertNotEquals($expected, strval($lastIdB));
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

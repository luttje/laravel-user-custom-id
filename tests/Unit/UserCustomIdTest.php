<?php

namespace Luttje\UserCustomId\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;
use Luttje\UserCustomId\FormatChunks\Literal;
use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Luttje\UserCustomId\Tests\TestCase;
use Orchestra\Testbench\Factories\UserFactory;

final class UserCustomIdTest extends TestCase
{
    private function createCustomId(
        Model $owner,
        Model|string $targetOrClass,
        string $format,
        ?string $targetAttribute = null,
        ?FormatChunkCollection $lastValueChunks = null,
    ) {
        return UserCustomId::create($targetOrClass, $owner, $format, $targetAttribute, $lastValueChunks);
    }

    private function createOwnerWithCustomId(
        Model|string $targetOrClass,
        string $format,
        ?string $targetAttribute = null,
        ?FormatChunkCollection $lastValueChunks = null,
    ) {
        $owner = UserFactory::new()->create();
        $this->createCustomId($owner, $targetOrClass, $format, $targetAttribute, $lastValueChunks);

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
        $lastValueChunks = new FormatChunkCollection([
            //'prefix-123455SUFFIX';
            $this->makeLiteral('prefix-'),
            $this->makeChunk('increment', 123455),
            $this->makeLiteral('SUFFIX'),
        ]);
        $expected = 'prefix-123456SUFFIX';

        $chunks = UserCustomId::generate($format, $lastValueChunks);
        $result = UserCustomId::convertToString($chunks);

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

        $result = UserCustomId::generateFor(Category::class, $owner);

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

        $this->assertEquals($expected, UserCustomId::convertToString($lastId));
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

        $this->assertEquals($expected, UserCustomId::convertToString($lastId));
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

        $this->assertEquals($expected, UserCustomId::convertToString($lastId));

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

    public function testGenerateForClassInstanceInsideTransaction()
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

        DB::beginTransaction();
        $category->save();
        DB::commit();

        $this->assertDatabaseHas('categories', [
            'id' => $expected,
        ]);

        $ownerCustomIdExists = \Luttje\UserCustomId\UserCustomId::latest()->first();

        $lastId = $ownerCustomIdExists->last_target_custom_id;

        $this->assertNotNull($lastId);
        $this->assertEquals($expected, UserCustomId::convertToString($lastId));
    }

    public function testGenerateForClassInstanceInsideRollbackTransaction()
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

        DB::beginTransaction();
        $category->save();
        DB::rollBack();

        $this->assertDatabaseMissing('categories', [
            'id' => $expected,
        ]);

        $ownerCustomIdExists = \Luttje\UserCustomId\UserCustomId::latest()->first();

        $lastId = $ownerCustomIdExists->last_target_custom_id;

        $this->assertNull($lastId);
    }

    public function testGenerateForClassInstanceInsideFailingTransaction()
    {
        $format = 'prefix-{increment}SUFFIX';
        $expected = 'prefix-1SUFFIX';

        $owner = $this->createOwnerWithCustomId(Category::class, $format, 'id');

        $category = new Category([
            // 'name' => 'Test Category', // Should fail because of missing name.
            'slug' => 'test-category',
            'description' => 'This is a test category.',
            'owner_id' => $owner->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $category->save();

        $this->assertDatabaseMissing('categories', [
            'custom_id' => $expected,
        ]);

        $ownerCustomIdExists = \Luttje\UserCustomId\UserCustomId::latest()->first();

        $lastId = $ownerCustomIdExists->last_target_custom_id;

        $this->assertNull($lastId);
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
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'This is a test product.',
            'category_id' => $category->id,
            'custom_id' => UserCustomId::generateFor(Product::class, $owner),
        ]);

        $category->refresh();

        $this->assertTrue($category->products->contains($product));
    }
}

<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\Tests\Fixtures\MockRandomChunk;
use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Orchestra\Testbench\Factories\UserFactory;

// <?php

// namespace Luttje\UserCustomId\FormatChunks;

// use Illuminate\Database\Eloquent\Model;

// class TargetAttribute extends FormatChunk
// {
//     public static function getChunkId(): string
//     {
//         return 'attribute';
//     }

//     public static function getParameters(): array
//     {
//         return [
//             new FormatChunkParameter('attribute', 'string'),
//         ];
//     }

//     public function getNextValue(Model $target, Model $owner): mixed
//     {
//         $hidden = $target->getHidden();
//         $attribute = $this->getParameterValue('attribute');

//         if (in_array($attribute, $hidden)) {
//             return '***';
//         }

//         return $target->{$attribute};
//     }
// }

final class TargetAttributeTest extends FormatChunkTestCase
{
    public function testTargetAttribute(): void
    {
        $chunk = $this->getChunk('attribute', ['name']);

        $owner = UserFactory::new()->create();
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('Test Category', $chunk->getNextValue($category, $owner));
    }

    public function testTargetAttributeWithSubstringStart(): void
    {
        $chunk = $this->getChunk('attribute', [
            'name',
            5,
        ]);

        $owner = UserFactory::new()->create();
        $category = new Category([
            'name' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('67890', $chunk->getNextValue($category, $owner));
    }

    public function testTargetAttributeWithSubstringLength(): void
    {
        $chunk = $this->getChunk('attribute', [
            'name',
            0,
            5,
        ]);

        $owner = UserFactory::new()->create();
        $category = new Category([
            'name' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('12345', $chunk->getNextValue($category, $owner));
    }

    public function testTargetAttributeWithSubstringStartAndLength(): void
    {
        $chunk = $this->getChunk('attribute', [
            'name',
            5,
            5,
        ]);

        $owner = UserFactory::new()->create();
        $category = new Category([
            'name' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('67890', $chunk->getNextValue($category, $owner));
    }

    public function testTargetAttributeCannotAccessNonExistant()
    {
        $chunk = $this->getChunk('attribute', ['asdasdasdasdasdasd']);

        $owner = UserFactory::new()->create();
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('', $chunk->getNextValue($category, $owner));
    }

    public function testTargetAttributeCannotAccessHidden()
    {
        $chunk = $this->getChunk('attribute', ['id']);

        $owner = UserFactory::new()->create();
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('***', $chunk->getNextValue($category, $owner));
    }

    public function testTargetAttributeCannotAccessHiddenSubstring()
    {
        $chunk = $this->getChunk('attribute', [
            'name',
            0,
            5,
        ]);

        $owner = UserFactory::new()->create();
        $category = new Category([
            'name' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $category->setHidden(['name']);

        $this->assertEquals('***', $chunk->getNextValue($category, $owner));
    }

    public function testTargetAttributeInRelationship()
    {
        $chunk = $this->getChunk('attribute', [
            'owner.name',
        ]);

        $owner = UserFactory::new()->create([
            'name' => 'Test User',
        ]);
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('Test User', $chunk->getNextValue($category, $owner));
    }

    // Currently fails
    public function testTargetAttributeCannotAccessHiddenInRelationship()
    {
        $chunk = $this->getChunk('attribute', [
            'category.name',
        ]);

        $owner = UserFactory::new()->create([
            'name' => 'Test User',
        ]);
        $category = new Category([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'owner_id' => $owner->id,
        ]);
        $category->save();

        $product = new Product([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Test Description',
            'custom_id' => 'test-product',
            'category_id' => $category->id,
        ]);

        $product->save();

        $product->refresh();

        $category->setHidden(['name']);

        $this->assertEquals('***', $chunk->getNextValue($product, $owner));
    }
}

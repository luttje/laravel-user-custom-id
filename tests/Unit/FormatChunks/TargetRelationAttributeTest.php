<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Luttje\UserCustomId\Tests\Fixtures\Models\User;

final class TargetRelationAttributeTest extends FormatChunkTestCase
{
    public function test_target_attribute_in_relationship()
    {
        $chunk = $this->getChunk('attribute-relation', [
            'owner',
            'name',
        ]);

        $owner = User::factory()->create([
            'name' => 'Test User',
        ]);
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('Test User', $chunk->getNextValue($category, $owner));
    }

    public function test_target_attribute_cannot_access_hidden_in_relationship()
    {
        $chunk = $this->getChunk('attribute-relation', [
            'category',
            'id',
        ]);

        $owner = User::factory()->create([
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

        $this->assertEquals('***', $chunk->getNextValue($product, $owner));
    }

    public function test_target_attribute_cannot_access_relationship_in_relationship()
    {
        $chunk = $this->getChunk('attribute-relation', [
            'category',
            'products',
        ]);

        $owner = User::factory()->create([
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

        $this->assertEquals('***', $chunk->getNextValue($product, $owner));
    }
}

<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Luttje\UserCustomId\Tests\Fixtures\Models\User;

final class TargetAttributeTest extends FormatChunkTestCase
{
    public function test_target_attribute(): void
    {
        $chunk = $this->getChunk('attribute', ['name']);

        $owner = User::factory()->create();
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('Test Category', $chunk->getNextValue($category, $owner));
    }

    public function test_target_attribute_with_substring_start(): void
    {
        $chunk = $this->getChunk('attribute', [
            'name',
            5,
        ]);

        $owner = User::factory()->create();
        $category = new Category([
            'name' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('67890', $chunk->getNextValue($category, $owner));
    }

    public function test_target_attribute_with_substring_length(): void
    {
        $chunk = $this->getChunk('attribute', [
            'name',
            0,
            5,
        ]);

        $owner = User::factory()->create();
        $category = new Category([
            'name' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('12345', $chunk->getNextValue($category, $owner));
    }

    public function test_target_attribute_with_substring_start_and_length(): void
    {
        $chunk = $this->getChunk('attribute', [
            'name',
            5,
            5,
        ]);

        $owner = User::factory()->create();
        $category = new Category([
            'name' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('67890', $chunk->getNextValue($category, $owner));
    }

    public function test_target_attribute_cannot_access_non_existant()
    {
        $chunk = $this->getChunk('attribute', ['asdasdasdasdasdasd']);

        $owner = User::factory()->create();
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('', $chunk->getNextValue($category, $owner));
    }

    public function test_target_attribute_cannot_access_hidden()
    {
        $chunk = $this->getChunk('attribute', ['id']);

        $owner = User::factory()->create();
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('***', $chunk->getNextValue($category, $owner));
    }

    public function test_target_attribute_cannot_access_hidden_substring()
    {
        $chunk = $this->getChunk('attribute', [
            'id',
            0,
            5,
        ]);

        $owner = User::factory()->create();
        $category = new Category([
            'id' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('***', $chunk->getNextValue($category, $owner));
    }

    public function test_target_attribute_cannot_access_relationship()
    {
        $chunk = $this->getChunk('attribute', [
            'products',
        ]);

        $owner = User::factory()->create([
            'name' => 'Test User',
        ]);
        $category = new Category([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test Description',
            'owner_id' => $owner->id,
        ]);
        $category->save();

        $category->products()->save(new Product([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'description' => 'Test Description',
            'custom_id' => 'test-product',
        ]));

        $this->assertEquals('***', $chunk->getNextValue($category, $owner));
    }
}

<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Luttje\UserCustomId\Tests\Fixtures\Models\User;

final class TargetAttributeTest extends FormatChunkTestCase
{
    public function testTargetAttribute(): void
    {
        $chunk = $this->getChunk('attribute', ['name']);

        $owner = User::factory()->create();
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

        $owner = User::factory()->create();
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

        $owner = User::factory()->create();
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

        $owner = User::factory()->create();
        $category = new Category([
            'name' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('67890', $chunk->getNextValue($category, $owner));
    }

    public function testTargetAttributeCannotAccessNonExistant()
    {
        $chunk = $this->getChunk('attribute', ['asdasdasdasdasdasd']);

        $owner = User::factory()->create();
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('', $chunk->getNextValue($category, $owner));
    }

    public function testTargetAttributeCannotAccessHidden()
    {
        $chunk = $this->getChunk('attribute', ['id']);

        $owner = User::factory()->create();
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('***', $chunk->getNextValue($category, $owner));
    }

    public function testTargetAttributeCannotAccessHiddenSubstring()
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

    public function testTargetAttributeCannotAccessRelationship()
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

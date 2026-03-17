<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
use Luttje\UserCustomId\Tests\Fixtures\Models\User;

final class OwnerAttributeTest extends FormatChunkTestCase
{
    public function test_owner_attribute(): void
    {
        $chunk = $this->getChunk('attribute-owner', ['name']);

        $owner = User::factory()->create([
            'name' => 'Test User',
        ]);
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('Test User', $chunk->getNextValue($category, $owner));
    }

    public function test_owner_attribute_with_substring_start(): void
    {
        $chunk = $this->getChunk('attribute-owner', [
            'name',
            5,
        ]);

        $owner = User::factory()->create([
            'name' => 'Test User',
        ]);
        $category = new Category([
            'name' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('User', $chunk->getNextValue($category, $owner));
    }

    public function test_owner_attribute_with_substring_length(): void
    {
        $chunk = $this->getChunk('attribute-owner', [
            'name',
            0,
            5,
        ]);

        $owner = User::factory()->create([
            'name' => 'Test User',
        ]);
        $category = new Category([
            'name' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('Test ', $chunk->getNextValue($category, $owner));
    }

    public function test_owner_attribute_with_substring_start_and_length(): void
    {
        $chunk = $this->getChunk('attribute-owner', [
            'name',
            5,
            5,
        ]);

        $owner = User::factory()->create([
            'name' => 'Test User',
        ]);
        $category = new Category([
            'name' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('User', $chunk->getNextValue($category, $owner));
    }

    public function test_owner_attribute_cannot_access_non_existant()
    {
        $chunk = $this->getChunk('attribute-owner', ['asdasdasdasdasdasd']);

        $owner = User::factory()->create([
            'name' => 'Test User',
        ]);
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $this->assertEquals('', $chunk->getNextValue($category, $owner));
    }

    public function test_owner_attribute_cannot_access_hidden()
    {
        $chunk = $this->getChunk('attribute-owner', ['id']);

        $owner = User::factory()->create([
            'name' => 'Test User',
        ]);
        $category = new Category([
            'name' => 'Test Category',
            'owner_id' => $owner->id,
        ]);

        $owner->setHidden(['id']);

        $this->assertEquals('***', $chunk->getNextValue($category, $owner));
    }

    public function test_owner_attribute_cannot_access_hidden_substring()
    {
        $chunk = $this->getChunk('attribute-owner', [
            'id',
            0,
            5,
        ]);

        $owner = User::factory()->create([
            'name' => 'Test User',
        ]);
        $category = new Category([
            'id' => '1234567890',
            'owner_id' => $owner->id,
        ]);

        $owner->setHidden(['id']);

        $this->assertEquals('***', $chunk->getNextValue($category, $owner));
    }

    public function test_owner_attribute_cannot_access_relationship()
    {
        $chunk = $this->getChunk('attribute-owner', [
            'categories',
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

        $this->assertEquals('***', $chunk->getNextValue($category, $owner));
    }
}

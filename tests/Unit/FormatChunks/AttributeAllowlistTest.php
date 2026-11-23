<?php

namespace Luttje\UserCustomId\Tests\Unit\FormatChunks;

use Luttje\UserCustomId\Contracts\ExposesCustomIdAttributes;
use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Luttje\UserCustomId\Tests\Fixtures\Models\User;

/**
 * A model that never opted in to exposing attributes.
 */
class UnexposedUser extends \Illuminate\Foundation\Auth\User
{
    protected $table = 'users';
}

/**
 * A model that protects its attributes with $visible instead of $hidden.
 */
class VisibleOnlyUser extends User
{
    protected $table = 'users';

    protected $hidden = [];

    protected $visible = ['name'];
}

/**
 * A model that (mistakenly) exposes things that are not plain values.
 */
class OverExposedUser extends User
{
    protected $table = 'users';

    protected $casts = [
        'email_verified_at' => 'datetime',
        'name' => 'array',
    ];

    public function getCustomIdAttributes(): array
    {
        return ['name', 'categories', 'password', 'email_verified_at'];
    }
}

final class AttributeAllowlistTest extends FormatChunkTestCase
{
    public function test_model_without_contract_exposes_nothing(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $owner = UnexposedUser::find($user->id);

        $this->assertNotInstanceOf(ExposesCustomIdAttributes::class, $owner);

        foreach (['name', 'email', 'password', 'id'] as $attribute) {
            $chunk = $this->getChunk('attribute-owner', [$attribute]);

            $this->assertEquals('***', $chunk->getNextValue(new Category, $owner), $attribute);
        }
    }

    public function test_unlisted_attributes_are_masked_even_when_not_hidden(): void
    {
        $owner = User::factory()->create(['email' => 'private@example.com']);

        $this->assertNotContains('email', $owner->getHidden());

        $chunk = $this->getChunk('attribute-owner', ['email']);

        $this->assertEquals('***', $chunk->getNextValue(new Category, $owner));
    }

    public function test_visible_only_model_does_not_leak_unlisted_attributes(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);
        $owner = VisibleOnlyUser::find($user->id);

        $this->assertEquals('***', $this->getChunk('attribute-owner', ['password'])->getNextValue(new Category, $owner));
        $this->assertEquals('Test User', $this->getChunk('attribute-owner', ['name'])->getNextValue(new Category, $owner));
    }

    public function test_explicitly_listed_attribute_is_readable_even_when_hidden(): void
    {
        $user = User::factory()->create();
        $owner = OverExposedUser::find($user->id);

        $this->assertContains('password', $owner->getHidden());

        $chunk = $this->getChunk('attribute-owner', ['password']);

        $this->assertEquals($user->password, $chunk->getNextValue(new Category, $owner));
    }

    public function test_listed_relation_or_array_is_masked(): void
    {
        $user = User::factory()->create(['name' => '["a","b"]']);
        $owner = OverExposedUser::find($user->id);

        $this->assertEquals('***', $this->getChunk('attribute-owner', ['categories'])->getNextValue(new Category, $owner));
        $this->assertEquals('***', $this->getChunk('attribute-owner', ['name'])->getNextValue(new Category, $owner));
    }

    public function test_listed_stringable_value_is_readable(): void
    {
        $user = User::factory()->create(['email_verified_at' => '2024-01-02 03:04:05']);
        $owner = OverExposedUser::find($user->id);

        $chunk = $this->getChunk('attribute-owner', ['email_verified_at', 0, 4]);

        $this->assertEquals('2024', $chunk->getNextValue(new Category, $owner));
    }

    public function test_relation_attribute_must_be_listed_on_target_with_dot_notation(): void
    {
        $owner = User::factory()->create(['name' => 'Test User']);
        $category = Category::withoutEvents(fn () => Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'owner_id' => $owner->id,
        ]));
        $product = new Product(['category_id' => $category->id]);

        // 'category.name' is listed on Product
        $this->assertEquals(
            'Test Category',
            $this->getChunk('attribute-relation', ['category', 'name'])->getNextValue($product, $owner)
        );

        // 'category.slug' is not, even though nothing hides it
        $this->assertEquals(
            '***',
            $this->getChunk('attribute-relation', ['category', 'slug'])->getNextValue($product, $owner)
        );

        // 'name' is listed on Category itself, but 'owner.name' is what Product would need
        $this->assertEquals(
            '***',
            $this->getChunk('attribute-relation', ['owner', 'name'])->getNextValue($product, $owner)
        );
    }

    public function test_relation_attribute_is_empty_when_relation_is_missing(): void
    {
        $owner = User::factory()->create();
        $product = new Product;

        $chunk = $this->getChunk('attribute-relation', ['category', 'name']);

        $this->assertNull($chunk->getNextValue($product, $owner));
    }
}

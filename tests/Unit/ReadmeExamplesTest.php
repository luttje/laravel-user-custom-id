<?php

namespace Luttje\UserCustomId\Tests\Unit;

use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Luttje\UserCustomId\Tests\Fixtures\Models\User;
use Luttje\UserCustomId\Tests\TestCase;

final class ReadmeExamplesTest extends TestCase
{
    public static function exampleCreateFormat()
    {
        $user = auth()->user();

        UserCustomId::createFormat(
            Product::class,
            $user,
            'product-{attribute-owner:name}-{random:5}-{increment}',
            'custom_id'
        );
    }

    public static function exampleGenerateIdInFormat(?Product &$product)
    {
        $user = auth()->user(); // Logged in as 'Jane'

        $product = new Product([
            'name' => 'Jacket',
            'slug' => 'jacket',
            'description' => 'Hand crafted jacket, by me (Jane).',
        ]);

        UserCustomId::generateFor($product, $user);

        $product->save();
    }

    public function testExampleBasics(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane',
        ]);

        $this->actingAs($user);

        $this->exampleCreateFormat();

        self::exampleGenerateIdInFormat($product);

        $this->assertStringStartsWith('product-Jane-', $product->custom_id);
        $this->assertStringEndsWith('-1', $product->custom_id);
    }

    public static function exampleCreateAutoFormat()
    {
        $user = auth()->user();

        UserCustomId::createFormat(
            Category::class,
            $user,
            'CAT{increment}',
            'id'
        );
    }

    public static function exampleGenerateAutoIdInFormat()
    {
        $user = auth()->user();

        // echo $user->categories()->count(); // 122

        $category = Category::create([
            'name' => 'Clothes',
            'slug' => 'clothes-category',
            'description' => 'General clothes and wearables.',
            'owner_id' => $user->id,
        ]);

        // The id attribute is now set to something like:
        // 'CAT123'

        return $category;
    }

    public function testExampleTrait(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->exampleCreateAutoFormat();

        $category = self::exampleGenerateAutoIdInFormat();

        $this->assertEquals('CAT1', $category->id);
    }
}

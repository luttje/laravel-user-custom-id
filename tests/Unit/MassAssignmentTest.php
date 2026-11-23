<?php

namespace Luttje\UserCustomId\Tests\Unit;

use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\Tests\Fixtures\Models\Category;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Luttje\UserCustomId\Tests\Fixtures\Models\User;
use Luttje\UserCustomId\Tests\TestCase;

final class MassAssignmentTest extends TestCase
{
    public function test_only_the_format_can_be_mass_assigned(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $customId = UserCustomId::createFormat(Product::class, $owner, 'INV-{increment}', 'custom_id');

        // What a controller that passes all request data along would do.
        $customId->update([
            'format' => 'ORDER-{increment}',
            'target_attribute' => 'price',
            'target_type' => Category::class,
            'owner_id' => $attacker->id,
            'owner_type' => Category::class,
            'last_target_custom_id' => '[]',
        ]);

        $customId->refresh();

        $this->assertSame('ORDER-{increment}', $customId->format);
        $this->assertSame('custom_id', $customId->target_attribute);
        $this->assertSame(Product::class, $customId->target_type);
        $this->assertEquals($owner->id, $customId->owner_id);
        $this->assertSame($owner->getMorphClass(), $customId->owner_type);
        $this->assertNull($customId->last_target_custom_id);
    }

    public function test_configuration_cannot_be_created_from_mass_assignment(): void
    {
        $owner = User::factory()->create();

        // Without the owner and target the row is refused by the database,
        // so a configuration can only be made through createFormat.
        $this->assertThrows(fn () => \Luttje\UserCustomId\UserCustomId::create([
            'format' => 'INV-{increment}',
            'target_attribute' => 'price',
            'target_type' => Product::class,
            'owner_id' => $owner->id,
            'owner_type' => $owner->getMorphClass(),
        ]));

        $this->assertDatabaseCount('user_custom_ids', 0);
    }

    public function test_create_format_still_sets_everything(): void
    {
        $owner = User::factory()->create();
        $customId = UserCustomId::createFormat(Product::class, $owner, 'INV-{increment}', 'custom_id');

        $this->assertDatabaseHas('user_custom_ids', [
            'id' => $customId->id,
            'format' => 'INV-{increment}',
            'target_attribute' => 'custom_id',
            'target_type' => Product::class,
            'owner_id' => $owner->id,
            'owner_type' => $owner->getMorphClass(),
        ]);

        $this->assertSame('INV-1', UserCustomId::generateFor(new Product, $owner));
        $this->assertSame('INV-1', (string) $customId->fresh()->last_target_custom_id);
    }
}

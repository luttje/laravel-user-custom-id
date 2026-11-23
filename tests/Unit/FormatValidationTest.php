<?php

namespace Luttje\UserCustomId\Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Luttje\UserCustomId\Exceptions\InvalidFormatException;
use Luttje\UserCustomId\Facades\UserCustomId;
use Luttje\UserCustomId\FormatChunks\FormatChunkCollection;
use Luttje\UserCustomId\FormatChunks\Increment;
use Luttje\UserCustomId\Rules\ValidFormat;
use Luttje\UserCustomId\Tests\Fixtures\Models\Product;
use Luttje\UserCustomId\Tests\Fixtures\Models\User;
use Luttje\UserCustomId\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class FormatValidationTest extends TestCase
{
    public static function validFormats(): array
    {
        return [
            ['product-{attribute-owner:name}-{random:5}-{increment}'],
            ['{increment}'],
            ['{increment:-1}'],
            ['{increment:0.5}'],
            ['{increment:1:3:.}'],
            ['{random:255}'],
            ['{random:5:ABC}'],
            ['{uuid}'],
            ['{year}-{month:m}-{day}'],
            ['just a literal'],
            ['0'],
            ['\{random:5\}'],
            ['{increment}-{increment:10}'],
        ];
    }

    public static function invalidFormats(): array
    {
        return [
            'empty' => [''],
            'too long' => [str_repeat('a', 256)],
            'unknown chunk' => ['{nope}'],
            'stray closing brace' => ['a}'],
            'literal chunk' => ['{literal}'],
            'random too long' => ['{random:256}'],
            'random way too long' => ['{random:999999999}'],
            'random zero length' => ['{random:0}'],
            'random negative length' => ['{random:-5}'],
            'random not a number' => ['{random:abc}'],
            'random no characters' => ['{random:5:}'],
            'increment not a number' => ['{increment:x}'],
            'increment zero' => ['{increment:0}'],
            'increment infinite' => ['{increment:1e999}'],
            'increment huge' => ['{increment:1e308}'],
            'increment imprecise' => ['{increment:9007199254740993}'],
            'increment digit as group symbol' => ['{increment:1:1:1}'],
            'increment negative grouping' => ['{increment:1:-3}'],
            'attribute missing' => ['{attribute-owner}'],
            'relation attribute missing' => ['{attribute-relation:category}'],
        ];
    }

    #[DataProvider('validFormats')]
    public function test_valid_formats_are_accepted(string $format): void
    {
        UserCustomId::validateFormat($format);

        $this->assertTrue(UserCustomId::isValidFormat($format));
    }

    #[DataProvider('invalidFormats')]
    public function test_invalid_formats_are_rejected(string $format): void
    {
        $this->assertFalse(UserCustomId::isValidFormat($format));

        $this->expectException(InvalidFormatException::class);

        UserCustomId::validateFormat($format);
    }

    public function test_create_format_rejects_invalid_format(): void
    {
        $owner = User::factory()->create();

        $this->assertThrows(
            fn () => UserCustomId::createFormat(Product::class, $owner, '{random:999999999}', 'custom_id'),
            InvalidFormatException::class
        );

        $this->assertDatabaseCount('user_custom_ids', 0);
    }

    public function test_updating_to_invalid_format_is_rejected(): void
    {
        $owner = User::factory()->create();
        $customId = UserCustomId::createFormat(Product::class, $owner, '{increment}', 'custom_id');

        $this->assertThrows(
            fn () => $customId->update(['format' => '{nope}']),
            InvalidFormatException::class
        );

        $this->assertSame('{increment}', $customId->fresh()->format);
    }

    public function test_validation_rule(): void
    {
        $rules = ['format' => ['required', new ValidFormat]];

        $this->assertTrue(Validator::make(['format' => 'INV-{increment}'], $rules)->passes());

        $validator = Validator::make(['format' => 'INV-{random:999999999}'], $rules);

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('between 1 and 255', $validator->errors()->first('format'));

        $this->assertTrue(Validator::make(['format' => ['array']], $rules)->fails());
    }

    public function test_chunks_of_the_same_type_keep_their_own_value(): void
    {
        $owner = User::factory()->create();
        UserCustomId::createFormat(Product::class, $owner, '{increment}-{increment:10}', 'custom_id');

        $ids = [];

        for ($i = 0; $i < 3; $i++) {
            $ids[] = UserCustomId::generateFor(new Product, $owner);
        }

        $this->assertSame(['1-10', '2-20', '3-30'], $ids);
    }

    public function test_literal_zero_is_kept(): void
    {
        $owner = User::factory()->create();
        UserCustomId::createFormat(Product::class, $owner, '0{increment}0', 'custom_id');

        $this->assertSame('010', UserCustomId::generateFor(new Product, $owner));
    }

    public function test_increment_stops_at_maximum_instead_of_repeating(): void
    {
        $owner = User::factory()->create();
        $lastValueChunks = new FormatChunkCollection([
            new Increment,
        ]);
        $lastValueChunks->first()->setValue(Increment::MAX_VALUE - 1);

        UserCustomId::createFormat(Product::class, $owner, '{increment}', 'custom_id', $lastValueChunks);

        $this->assertSame((string) Increment::MAX_VALUE, UserCustomId::generateFor(new Product, $owner));

        $this->assertThrows(
            fn () => UserCustomId::generateFor(new Product, $owner),
            \OverflowException::class
        );

        // The stored counter must still be usable (it used to be corrupted by INF).
        $stored = \Luttje\UserCustomId\UserCustomId::first()->last_target_custom_id;

        $this->assertSame((string) Increment::MAX_VALUE, (string) $stored);
    }
}

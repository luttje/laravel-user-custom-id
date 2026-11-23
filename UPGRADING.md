# Upgrading

## From 0.4 to 0.5

This release hardens the package for formats written by users you don't fully trust. Three changes need action in your application, the rest is good to know.

### 1. Models must list the attributes that formats may read

**Action required if your formats use `{attribute:…}`, `{attribute-owner:…}` or `{attribute-relation:…}`.**

Before, a format could read every attribute that was not in the `$hidden` property of a model (and was not a relation). Now a format can only read attributes that the model lists. Until you do this, those chunks show up as `***` in new ID's.

Implement `ExposesCustomIdAttributes` on every owner and target model that formats should read from:

```php
use Luttje\UserCustomId\Contracts\ExposesCustomIdAttributes;

class Product extends Model implements ExposesCustomIdAttributes
{
    public function getCustomIdAttributes(): array
    {
        return [
            'name',          // allows {attribute:name}
            'category.name', // allows {attribute-relation:category:name}
        ];
    }
}
```

* Attributes of a related model are listed on the **target** model with dot notation. What the related model lists itself does not matter.
* `$hidden` and `$visible` are no longer looked at. Listing a hidden attribute exposes it.
* An attribute that is not listed shows as `***`, also when it does not exist on the model (that was an empty string).
* A relation without a related model (e.g. a product without category) now results in an empty string (it threw a `TypeError`).

Only list attributes that the user who writes the format is allowed to see.

### 2. Formats are validated when they are stored

**Action required: catch the exception or validate the input, and check the formats you already have.**

`UserCustomId::createFormat(...)` and changing the `format` of an existing configuration now throw a `Luttje\UserCustomId\Exceptions\InvalidFormatException` when the format can't be used. To show your users a validation error instead, add the rule where they submit their format:

```php
use Luttje\UserCustomId\Rules\ValidFormat;

$request->validate([
    'format' => ['required', 'string', new ValidFormat],
]);
```

A format is refused when:

* it is empty or longer than 255 characters;
* it contains a chunk that is not registered, or `{literal}`;
* `{random}` asks for less than 1 or more than 255 characters, or has an empty list of characters;
* `{increment}` has an amount of `0`, an amount of more than 14 digits, a negative `group-by` or a `group-symbol` that contains digits.

These limits also apply while generating. A format that is already in your database and breaks one of them will throw as soon as an ID is generated for it. Find those formats before you deploy:

```php
use Luttje\UserCustomId\Facades\UserCustomId as UserCustomIdFacade;
use Luttje\UserCustomId\UserCustomId;

UserCustomId::all()
    ->reject(fn (UserCustomId $customId) => UserCustomIdFacade::isValidFormat($customId->format))
    ->each(fn (UserCustomId $customId) => dump([$customId->id, $customId->format]));
```

An unknown chunk used to throw a plain `\Exception` while generating. It is now an `InvalidFormatException` (which extends `\InvalidArgumentException`).

### 3. Only the format can be mass assigned

**Action required if you create or update `Luttje\UserCustomId\UserCustomId` models yourself.**

`format` is now the only fillable attribute. `owner_id`, `owner_type`, `target_type`, `target_attribute` and `last_target_custom_id` are ignored by `fill()` and `update()`. If your application calls `Model::preventSilentlyDiscardingAttributes()`, a `MassAssignmentException` is thrown instead.

* `UserCustomId::create([...])` on the model no longer works: the owner and target stay empty, which the database refuses. Create configurations with the `UserCustomId::createFormat(...)` facade method.
* To change one of the other attributes, set it explicitly: `$customId->target_attribute = 'custom_id';`.

### Changes in generated ID's

* **Two chunks of the same type each keep their own value.** The format `{increment}-{increment:10}` used to give `1-10`, `2-11`, `3-12`. It now gives `1-10`, `2-20`, `3-30`. Existing formats like this continue with different numbers than before.
* **A literal `0` is no longer lost.** A piece of text that is exactly `0` was not recognized as text. At the end of a format it was dropped: `{increment}0` used to give `1` and now gives `10`. Directly before a chunk it made generating fail: `0{increment}` used to throw an exception and now gives `01`.
* **`{increment}` stops at 14 digits.** An `OverflowException` is thrown instead of handing out ID's that are no longer unique.
* **`{random}` uses a cryptographically secure generator** (`random_int` instead of `rand`).

### Generating is safe for simultaneous requests

`UserCustomId::generateFor(...)` now runs in a database transaction and locks the configuration of the owner. Two requests at the same time no longer get the same `{increment}` number. You don't have to change anything.

If you generate the ID and save the model inside a transaction of your own, the number is given back when that transaction is rolled back. See "Uniqueness" in the README.

### Publishing the migration

`php artisan vendor:publish --tag="user-custom-id-migrations"` did not publish anything before, this is fixed. The table itself is unchanged: if you already have the `user_custom_ids` table, there is nothing to migrate.

The package no longer contains a migration for the `users` table. It was only used by the tests of the package.

### For custom chunk types

* Override `validateParameters()` in your chunk to enforce limits on its parameters. It runs after the parameters have been cast, and should throw an `\InvalidArgumentException`.
* `FormatChunkRepository::getChunk()` has a third parameter `int $occurrence = 0`. Add it if you override that method.
* `Attribute::isAllowed()` now checks `getCustomIdAttributes()` of the model instead of `$hidden`.
* `UserCustomIdManager::parseFormat(string $format)` is public, for when you want to inspect the chunks of a format.
* `Random::getRandomNumber()` can still be overridden, e.g. to mock it in tests.

### Dependencies

`illuminate/database`, `illuminate/filesystem`, `illuminate/support` and `ramsey/uuid` are now listed as requirements. They are part of every Laravel application, so nothing changes for you.

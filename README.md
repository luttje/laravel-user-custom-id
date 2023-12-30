# 🏷 Laravel User Custom ID

**This Laravel package enables users to create and utilize custom ID styles within your application.**
For instance, a user like Jane can set a format such as `product-{attribute-owner:name}-{random:5}-{increment}` for product IDs.
Once configured, the application can generate IDs for Jane's products, like `product-Jane-z4AyW-1` for the first product, `product-Jane-3QJ5A-2` for the second product, etc.

<div align="center">

[![run-tests](https://github.com/luttje/laravel-user-custom-id/actions/workflows/run-tests.yml/badge.svg)](https://github.com/luttje/laravel-user-custom-id/actions/workflows/run-tests.yml)
[![Coverage Status](https://coveralls.io/repos/github/luttje/laravel-user-custom-id/badge.svg?branch=main)](https://coveralls.io/github/luttje/laravel-user-custom-id?branch=main)

</div>

> [!Warning]
> This package is still in development. It is not yet ready for production use and the API may change at any time.

## Installation

You can install the package via composer:

```bash
composer require luttje/laravel-user-custom-id
```

## 👨‍🔧 Usage

### Scenario

Imagine a webshop system with multiple shops. Users need customizable product IDs. For example:

* User A prefers sequential numbers like `123`, `124`, `125`.
* User B opts for a prefix with numbers like `A-123`, `A-124`.
* User Jane likes a complex format with ids like `product-Jane-z4AyW-1`, `product-Jane-3QJ5A-2`.

This package allows users to define their own ID style. The application can then generate ID's for their products based on the style they chose.

### Configuration

Users define their ID style for a model, usually upon creating their shop. They select the model they want to customize an id style for (e.g., `Product`) and input their desired style (e.g., `product-{attribute-owner:name}-{random:5}-{increment}`). The configuration is then created as follows:

<!-- #EXAMPLE_COPY_START = \Luttje\UserCustomId\Tests\Unit\ReadmeExamplesTest::exampleCreateFormat -->

```php
$user = auth()->user();

UserCustomId::createFormat(
    Product::class,
    $user,
    'product-{attribute-owner:name}-{random:5}-{increment}',
    'custom_id'
);
```

<!-- #EXAMPLE_COPY_END -->

This setup requires the target model (e.g., `Product`), the user, the ID style, and the attribute to store the ID (`custom_id`). Whenever a product belonging to the user is created, the ID will be generated according to this format. It will be stored in the `custom_id` attribute of the product.

### ID Generation

There are two methods to generate IDs:

#### 1. ✋ Manual ID generation

Generate an ID when a user creates a new product:

<!-- #EXAMPLE_COPY_START = \Luttje\UserCustomId\Tests\Unit\ReadmeExamplesTest::exampleGenerateIdInFormat -->

```php
$user = auth()->user(); // Logged in as 'Jane'

$product = new Product([
    'name' => 'Jacket',
    'slug' => 'jacket',
    'description' => 'Hand crafted jacket, by me (Jane).',
]);

UserCustomId::generateFor($product, $user);

$product->save();
```

<!-- #EXAMPLE_COPY_END -->

Based on the configuration the user created earlier, the ID will be generated as something like `product-Jane-FXALW-1`. It will be saved in the `custom_id` attribute of the product. If the user creates another product, its ID will be something akin to `product-Jane-3QJ5A-2`.

#### 2. 🤖 Automatically generating an ID

Apply the `HasUserCustomId` interface and `WithUserCustomId` trait to your model for auto-generation:

<!-- #EXAMPLE_COPY_START = { "symbol": "\\Luttje\\UserCustomId\\Tests\\Fixtures\\Models\\Category", "short": false } -->

```php
class Category extends Model implements HasUserCustomId
{
    use HasUuids;
    use WithUserCustomId;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'owner_id',
    ];

    protected $hidden = [
        'id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
```

<!-- #EXAMPLE_COPY_END -->

> [!NOTE]
> **Implement the getOwner() method in your model to define the ID style's owner. By default, it uses the owner relation, but you can customize it.**

With the above implementation an ID will automatically be generated upon [the Eloquent `creating` event](https://laravel.com/docs/10.x/eloquent#events) for the model. It will store the ID in the configured attribute of the model.

### Customizing the Owner Model

You can designate any model as the ID style's owner, not just the logged in user. Provide the owner model as the second argument to generateFor. Or have the `getOwner()` method in your model that implements the `HasUserCustomId` interface return the owner model.

Depending on your situation it might make more sense to have a `Tenant` or `Team` model be the owner of the ID style.

### Understanding Format Chunks

Formats are made of "chunks," placeholders for specific values. For example, `product-{attribute-owner:name}-{random:5}-{increment}` contains several chunks:

* `product-`
* `{attribute-owner:name}`: The owner model's name.
* `-`
* `{random:5}`: A random 5-character string.
* `-`
* `{increment}`: An incrementing number.

Each chunk follows the `{name:parameters}` format, with specific parameters for each type. Except for literal chunks, which are just literal strings (like `product-` and the `-` chunks above).

#### Chunk Types

* `{increment:amount:group-by:group-symbol}`: Incrementing numbers.
* `{attribute-owner:attribute:start:length}`: Owner model attribute value (or a part of it)
* `{random:length:characters}`: Random characters.
* `{attribute-target:attribute:start:length}`: Target model attribute value (or a part of it)
* `{attribute-relation:attribute:start:length}`: Related model attribute value (or a part of it)
* `{uuid:version}`: Universally unique identifier
* Time Chunks: `{year}`, `{month:format}`, `{day}`, etc.

> [&raquo; Check out the **🍪 Format Chunks** documentation](docs/format-chunks.md) to learn more about all available chunks and properties.

## Testing

Then run the tests with:

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
more information.
:

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
more information.

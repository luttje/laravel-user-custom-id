# 🏷 Laravel User Custom ID

Let your users configure an ID style and then use that ID style in your
application.

For example a user Jane might want to use an ID style like `product-{attribute-owner:name}-{random:5}-{increment}` for their products.

After creating that configuration, you can generate ID's for the user based on that ID style. For example: `product-Jane-z4AyW-1`.

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

## Usage

Imagine this scenario: you have a webshop system with multiple shops. 
You want your users to be able to configure their own ID style for a product.
For example, one user wants to use incrementing numbers like `123`, `124`, `125`, etc.
While another user wants to use a prefix with a number like `ABC-123`, `ABC-124`, `ABC-125`, etc.

To achieve this, you can use this package to let your users configure their own ID style.
After that, you can have the configured ID style used in your application.

### Configuration

First your user will have to configure their ID style for a specific model.
This usually happens only once, for example when they create their shop.

You might show the user a nice dropdown for the model they wish to configure (e.g: `Product`) and text input to let them configure their ID style. After they submit their chosen model and ID style (e.g. `product-{attribute-owner:name}-{random:5}-{increment}`), you create the configuration like this:

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

Whenever you create a user custom id configuration, you have to provide the target model (e.g. `Product`), the owning user (e.g.: the shop owner), the ID style (e.g. `product-{attribute-owner:name}-{random:5}-{increment}`) and the attribute in which the ID should be stored (e.g. `custom_id`).

### Generating an ID

There's two ways to generate id's after a user has configured them. The first way is to manually generate an ID for a model. The second way is to automatically generate an ID when a model is created.

#### ✋ Manual ID generation

When the user creates a new product, you can generate the ID for them based on the configuration they created earlier:

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

#### 🤖 Automatically generating an ID

You can also automatically generate an ID when a model is created.
To have formatted id's automatically generated you have to apply the `HasUserCustomId` interface to your model and use the `WithUserCustomId` trait:

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
> **You may have to implement the `getOwner()` method in your model.**
> This method should return the owner of the model. This information is used to determine which ID style to use.
> The default behaviour is to return the `owner` relation, so at least make sure you have a relation like that in your model.
> You can however override the `getOwner()` method to return any other model that is the owner of the ID style.

With the above implementation an ID will automatically be generated upon [the Eloquent `creating` event](https://laravel.com/docs/10.x/eloquent#events) for the model.
It will store the ID in the configured attribute of the model.

### Owner model

In the above examples, the owner of the ID style is the user. However, you can also use any other model as the owner of the ID style. Another common example might be to have a `Tenant` or `Team` model as the owner of the ID style.

Whenever you create a model, make sure to provide the owner model as the second argument to the `generateFor` method.

### Format Chunks

The format as seen above is a string made up of what we call "chunks". Each chunk is a placeholder for a specific value. Let's take a look at the format again:

```php
'product-{attribute-owner:name}-{random:5}-{increment}'
```

The above format has 6 chunks:

* `product-`: a `Literal` chunk that will display the literal string `product-`.
* `{attribute-owner:name}`: a chunk that will display the value of the `name` attribute of the owner model. In the examples above, this would be the name of the user.
* `-`: a chunk that will display the literal string `-`.
* `{random:5}`: a chunk that will display a random string of 5 characters.
* `-`: a chunk that will display the literal string `-`.
* `{increment}`: a chunk that will display an incrementing number. If the previous ID was `product-Jane-FXALW-1`, then the new id will have `2` as the number.

Except for the `Literal` chunk, each chunk has a name and a set of parameters. They're always formatted as `{name:parameters}`. Each chunk has its own set of parameters, separated by a colon (`:`).

If you ever need to use a literal `{` or `}` in your format, you can escape them by using a backslash (`\`). For example: `\{random:5\}` will display `{random:5}` as a literal string.

#### Available Format Chunks

##### `{increment:amount:group-by:group-symbol}`

Used to display an incrementing number.

* `amount`: the amount to increment by. Defaults to `1`.
* `group-by`: the amount of numbers to group together. Defaults to `0` to indicate no grouping.
* `group-symbol`: the symbol to use to separate the groups. Defaults to `-`.

**💻 Example:**

```php
'X{increment:1:3:.}S'
```

**🏷 Possible sequence of id's:**

* `X512.219S`
* `X512.220S`
* `X512.221S`

##### `{attribute-owner:attribute:start:length}`

Used to display the value of a specific attribute of the owner model.

* `attribute`: the name of the attribute to display.
* `start`: the start position of the value. Defaults to `0`.
* `length`: the length of the value. Defaults to `-1` to indicate the full length of the value.

**💻 Example:**

*(Imagine our owner model is a `User` model with the `name` 'Gerald')*

```php
'product-{attribute-owner:name:0:3}-{random:5}-{increment}'
```

**🏷 Possible sequence of id's:**

* `product-Ger-FXALW-123`
* `product-Ger-2sSAD-124`
* `product-Ger-3QJ5A-125`

##### `{random:length:characters}`

Filled with random characters.

* `length`: the amount of random characters to display.
* `characters`: the characters to pick from for the random string. Defaults to `ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789`.

**💻 Example:**

```php
`__{random:5}__`
```

**🏷 Possible sequence of id's:**

* `__FXALW__`
* `__2sSAD__`
* `__3QJ5A__`

##### `{attribute-target:attribute:start:length}`

Almost exactly the same as the [`{attribute-owner:attribute:start:length}`](#attribute-ownerattributestartlength) chunk, except that it uses the target model instead of the owner model.

**💻 Example:**

```php
'{attribute-target:category_id}/{increment}'
```

**🏷 Possible sequence of id's:**

*(Imagine our target model is a `Product` model with a `category_id` attribute of `42`)*

* `42/5001`
* `42/5002`
* `42/5003`

##### `{attribute-relation:attribute:start:length}`

Almost exactly the same as the [`{attribute-target:attribute:start:length}`](#attribute-ownerattributestartlength) chunk, except that it uses a relation of the target model instead of the owner model.

**💻 Example:**

```php
'{attribute-relation:category:name:5}@{increment}'
```

**🏷 Possible sequence of id's:**

*(Imagine our target model is a `Product` model with a related `Category` model with a `name` attribute of `Clothes`)*

* `Cloth@5001`
* `Cloth@5002`
* `Cloth@5003`

##### `{uuid:version}`

Used to display a UUID (Universally Unique IDentifier).

* `version`: the version of the UUID to display. Defaults to `4`, can also be `1`.

**💻 Example:**

```php
'{uuid:4}'
```

**🏷 Possible sequence of id's:**

* `027006be-693d-45c4-ae50-8155f165fead`
* `c3f00091-a131-4b5e-bd90-4baea611e3d0`
* `822b3899-3b94-4036-9cd8-afb3a0ec286b`

##### Time Format Chunks

The following chunks are used to display time related values:

* `{year}` - The current year.
* `{month:format}` - The current localized month (e.g.: `1` for January) or the current localized month name (e.g.: `January`)
* `{day}` - The current day.
* `{weekday}` - The current localized day of the week (e.g.: `Monday`)
* `{hour}` - The current hour.
* `{minute}` - The current minute.
* `{second}` - The current second.
* `{millisecond}` - The current millisecond.

The `{month:format}` chunk displays the months as a number by default. But you can supply the following format options:

* `{month:F}` - The full localized name of the current month. (e.g.: `January`)
* `{month:M}` - The short localized name of the current month. (e.g.: `Jan`)
* `{month:m}` - The current month as a number with leading zero. (e.g.: `01`)
* `{month:n}` - The current month as a number without leading zero. (e.g.: `1`)
* `{month:t}` - The amount of days in the current month. (e.g.: `31`)

The `{month}` and `{weekday}` chunks are localized. When they display a name for the month or weekday, they will use the current locale of the application. You can change the locale by using the `\Carbon\Carbon::setLocale('nl_NL')` function.

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

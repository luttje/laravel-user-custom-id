# 🍪 Format Chunks

Format chunks are used to define the format of the generated ID's. Let's look at an example:

```php
'product-{attribute-owner:name}-{random:5}-{increment}'
```

This example format has 6 chunks:

* `product-`: a `Literal` chunk that will display the literal string `product-`.
* `{attribute-owner:name}`: a chunk that will display the value of the `name` attribute of the owner model. In the examples in the README, this would be the name of the user.
* `-`: a chunk that will display the literal string `-`.
* `{random:5}`: a chunk that will display a random string of 5 characters.
* `-`: a chunk that will display the literal string `-`.
* `{increment}`: a chunk that will display an incrementing number. If the previous ID was `product-Jane-FXALW-1`, then the new id will have `2` as the number.

Except for the `Literal` chunk, each chunk has a name and a set of parameters. They're always formatted as `{name:parameters}`. Each chunk has its own set of parameters, separated by a colon (`:`).

## Available Format Chunks

### Literal

Literal chunks are used to display literal strings. They're not formatted as `{name:parameters}`, but just as a literal string. For example: `product-` is a literal chunk that will display the literal string `product-`.

Because `{` and `}` have special meaning, you can't use them as is. You can disable their special meaning by escaping them using a backslash (`\`). For example: `\{random:5\}` will display `{random:5}` as a literal string.

### `{increment:amount:group-by:group-symbol}`

Used to display an incrementing number.

* `amount`: the amount to increment by. Defaults to `1`. Cannot be `0`.
* `group-by`: the amount of numbers to group together. Defaults to `0` to indicate no grouping.
* `group-symbol`: the symbol to use to separate the groups. Defaults to `-`. Cannot contain digits.

The counter stops (an `OverflowException` is thrown) once it would go beyond 14 digits.

**💻 Example:**

```php
'X{increment:1:3:.}S'
```

**🏷 Possible sequence of id's:**

* `X512.219S`
* `X512.220S`
* `X512.221S`

### `{attribute-owner:attribute:start:length}`

Used to display the value of a specific attribute of the owner model.

> [!WARNING]
> The attribute must be listed in `getCustomIdAttributes()` of the model (see the `ExposesCustomIdAttributes` interface). Attributes that are not listed, and all attributes of models that don't implement the interface, are displayed as `***`. This goes for all attribute chunks.

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

### `{random:length:characters}`

Filled with random characters.

* `length`: the amount of random characters to display, between `1` and `255`. Defaults to `10`.
* `characters`: the characters to pick from for the random string. Defaults to `ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789`.

**💻 Example:**

```php
`__{random:5}__`
```

**🏷 Possible sequence of id's:**

* `__FXALW__`
* `__2sSAD__`
* `__3QJ5A__`

### `{attribute:attribute:start:length}`

Almost exactly the same as the [`{attribute-owner:attribute:start:length}`](#attribute-ownerattributestartlength) chunk, except that it uses the target model instead of the owner model.

**💻 Example:**

```php
'{attribute:category_id}/{increment}'
```

**🏷 Possible sequence of id's:**

*(Imagine our target model is a `Product` model with a `category_id` attribute of `42`)*

* `42/5001`
* `42/5002`
* `42/5003`

### `{attribute-relation:relation:attribute:start:length}`

Almost exactly the same as the [`{attribute:attribute:start:length}`](#attributeattributestartlength) chunk, except that it reads the attribute from a relation of the target model.

* `relation`: the name of the relation on the target model.
* `attribute`: the name of the attribute of the related model to display.

The **target** model decides what is exposed, using dot notation: `'category.name'` must be listed in the `getCustomIdAttributes()` of the target model.

**💻 Example:**

```php
'{attribute-relation:category:name:0:5}@{increment}'
```

**🏷 Possible sequence of id's:**

*(Imagine our target model is a `Product` model with a related `Category` model with a `name` attribute of `Clothes`)*

* `Cloth@5001`
* `Cloth@5002`
* `Cloth@5003`

### `{uuid:version}`

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

### Time Format Chunks

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

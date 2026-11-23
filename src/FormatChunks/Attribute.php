<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;
use Luttje\UserCustomId\Contracts\ExposesCustomIdAttributes;
use Luttje\UserCustomId\Traits\WithFormatChunkSubstring;

abstract class Attribute extends FormatChunk
{
    use WithFormatChunkSubstring;

    /**
     * Shown instead of the value when a format tries to read an attribute
     * that the model did not explicitly expose.
     */
    public const MASK = '***';

    public static function getParametersConfig(): array
    {
        return [
            new FormatChunkParameter('attribute', 'string'),
            ...static::getSubstringParametersConfig(),
        ];
    }

    /**
     * Whether a user-defined format may read the given attribute of the model.
     *
     * Nothing is readable unless the model opts in through the
     * ExposesCustomIdAttributes contract. For related models, pass the
     * dotted path as it's listed on the target (e.g. 'category.name').
     */
    public static function isAllowed(Model $model, string $attribute): bool
    {
        if (! $model instanceof ExposesCustomIdAttributes) {
            return false;
        }

        return in_array($attribute, $model->getCustomIdAttributes(), true);
    }

    public function getAttributeValue(Model $target): mixed
    {
        if (! self::isAllowed($target, $this->getParameter('attribute'))) {
            return self::MASK;
        }

        return $this->readAttribute($target);
    }

    /**
     * Reads the attribute without checking if that's allowed, the caller
     * must have done so.
     */
    protected function readAttribute(Model $model): mixed
    {
        $value = $model->{$this->getParameter('attribute')};

        if (is_null($value)) {
            return null;
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        // Relations, arrays and other things that are not a plain value
        // have no place in an id, even when they were listed by mistake.
        // (Models and collections are stringable: they'd be dumped as JSON.)
        if ($value instanceof Model || $value instanceof Enumerable) {
            return self::MASK;
        }

        if (! is_scalar($value) && ! $value instanceof \Stringable) {
            return self::MASK;
        }

        return $this->handleSubstring((string) $value);
    }
}

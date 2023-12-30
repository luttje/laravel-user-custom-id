<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\Traits\WithFormatChunkSubstring;

abstract class Attribute extends FormatChunk
{
    use WithFormatChunkSubstring;

    public static function getParametersConfig(): array
    {
        return [
            new FormatChunkParameter('attribute', 'string'),
            ...static::getSubstringParametersConfig(),
        ];
    }

    public static function isAllowed(Model $target, string $attribute): bool
    {
        $hidden = $target->getHidden();

        if (in_array($attribute, $hidden)) {
            return false;
        }

        // Check if the attribute is a relation.
        if (method_exists($target, $attribute)) {
            return false;
        }

        return true;
    }

    public function getAttributeValue(Model $target): mixed
    {
        $attribute = $this->getParameter('attribute');

        if (! self::isAllowed($target, $attribute)) {
            return '***';
        }

        $value = $target->{$attribute};

        if (is_null($value)) {
            return null;
        }

        return $this->handleSubstring($value);
    }
}

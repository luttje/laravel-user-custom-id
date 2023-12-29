<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;

class TargetRelationAttribute extends Attribute
{
    public static function getChunkId(): string
    {
        return 'attribute-relation';
    }

    public static function getParameters(): array
    {
        return [
            new FormatChunkParameter('relation', 'string'),
            new FormatChunkParameter('attribute', 'string'),
            ...static::getSubstringParameters(),
        ];
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        $relation = $this->getParameterValue('relation');

        $related = $target->{$relation};

        return $this->getAttributeValue($related);
    }
}

<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;

class TargetRelationAttribute extends Attribute
{
    public static function getChunkId(): string
    {
        return 'attribute-relation';
    }

    public static function getParametersConfig(): array
    {
        return [
            new FormatChunkParameter('relation', 'string'),
            new FormatChunkParameter('attribute', 'string'),
            ...static::getSubstringParametersConfig(),
        ];
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        $relation = $this->getParameter('relation');
        $attribute = $this->getParameter('attribute');

        // The target decides which attributes of its relations are exposed,
        // so check before the relation is even loaded.
        if (! self::isAllowed($target, $relation.'.'.$attribute)) {
            return self::MASK;
        }

        $related = $target->{$relation};

        if (! $related instanceof Model) {
            return null;
        }

        return $this->readAttribute($related);
    }
}

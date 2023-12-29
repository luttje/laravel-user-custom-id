<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;

class OwnerAttribute extends Attribute
{
    public static function getChunkId(): string
    {
        return 'attribute-owner';
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        return $this->getAttributeValue($owner);
    }
}

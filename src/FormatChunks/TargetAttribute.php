<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;

class TargetAttribute extends Attribute
{
    public static function getChunkId(): string
    {
        return 'attribute';
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        return $this->getAttributeValue($target);
    }
}

<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;

class Literal extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'literal';
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        return $this->value;
    }
}

<?php

namespace Luttje\UserCustomId\FormatChunks;

class Literal extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'literal';
    }

    public function getNextValue(): string
    {
        return $this->value;
    }
}

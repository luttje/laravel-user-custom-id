<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;

class Increment extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'increment';
    }

    public static function getParameters(): array
    {
        return [
            new FormatChunkParameter('amount', 'numeric', 1),
        ];
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        return (string) ((double) $this->value + (double) $this->getParameterValue('amount'));
    }
}

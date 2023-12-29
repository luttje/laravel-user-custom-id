<?php

namespace Luttje\UserCustomId\FormatChunks;

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

    public function getNextValue(): string
    {
        return (string) ((double) $this->value + (double) $this->getParameterValue('amount'));
    }
}

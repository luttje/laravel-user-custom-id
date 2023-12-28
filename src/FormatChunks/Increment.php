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
            new FormatChunkParameter('amount', 'integer', 1),
        ];
    }

    public function getNextValue(): string
    {
        return (string) ((int) $this->value + (int) $this->getParameterValue('amount'));
    }
}

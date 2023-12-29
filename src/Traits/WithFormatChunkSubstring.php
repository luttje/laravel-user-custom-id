<?php

namespace Luttje\UserCustomId\Traits;

use Luttje\UserCustomId\FormatChunks\FormatChunkParameter;

trait WithFormatChunkSubstring
{
    protected static function getSubstringParameters(): array
    {
        return [
            new FormatChunkParameter('start', 'integer', 0),
            new FormatChunkParameter('length', 'integer', -1),
        ];
    }

    protected function getSubstring(string $value): string
    {
        $start = (int) $this->getParameterValue('start');
        $length = (int) $this->getParameterValue('length');

        if ($length === -1) {
            return substr($value, $start);
        }

        return substr($value, $start, $length);
    }
}

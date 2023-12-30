<?php

namespace Luttje\UserCustomId\Traits;

use Luttje\UserCustomId\FormatChunks\FormatChunkParameter;

trait WithFormatChunkSubstring
{
    protected static function getSubstringParametersConfig(): array
    {
        return [
            new FormatChunkParameter('start', 'integer', 0),
            new FormatChunkParameter('length', 'integer', -1),
        ];
    }

    protected function handleSubstring(string $value): string
    {
        $start = (int) $this->getParameter('start');
        $length = (int) $this->getParameter('length');

        if ($length === -1) {
            return substr($value, $start);
        }

        return substr($value, $start, $length);
    }
}

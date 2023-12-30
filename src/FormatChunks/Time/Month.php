<?php

namespace Luttje\UserCustomId\FormatChunks\Time;

use Illuminate\Database\Eloquent\Model;
use Luttje\UserCustomId\FormatChunks\FormatChunk;
use Luttje\UserCustomId\FormatChunks\FormatChunkParameter;

class Month extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'month';
    }

    public static function getParametersConfig(): array
    {
        return [
            new FormatChunkParameter('format', 'string', 'n'),
        ];
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        // https://www.php.net/manual/en/datetime.format.php
        $validMonthFormats = [
            'F', 'M', 'm', 'n', 't',
        ];
        $format = (string) $this->getParameter('format');

        if (! in_array($format, $validMonthFormats)) {
            $format = 'n';
        }

        return now()->translatedFormat($format);
    }
}

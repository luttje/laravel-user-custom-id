<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;

class TargetAttribute extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'attribute';
    }

    public static function getParameters(): array
    {
        return [
            new FormatChunkParameter('attribute', 'string'),
            new FormatChunkParameter('start', 'integer', 0),
            new FormatChunkParameter('length', 'integer', -1),
        ];
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        $hidden = $target->getHidden();
        $attribute = $this->getParameterValue('attribute');

        if (in_array($attribute, $hidden)) {
            return '***';
        }

        $value = data_get($target, $attribute);

        $start = (int) $this->getParameterValue('start');
        $length = (int) $this->getParameterValue('length');

        if ($length === -1) {
            return substr($value, $start);
        }

        return substr($value, $start, $length);
    }
}

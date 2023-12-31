<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;

class Increment extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'increment';
    }

    public static function getParametersConfig(): array
    {
        return [
            new FormatChunkParameter('amount', 'numeric', 1),
            new FormatChunkParameter('group-by', 'integer', 0),
            new FormatChunkParameter('group-symbol', 'string', '-'),
        ];
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        $amount = (float) $this->getParameter('amount');
        $groups = (int) $this->getParameter('group-by');
        $groupSymbol = (string) $this->getParameter('group-symbol');

        $value = $this->value;

        // Remove group symbols
        if ($groups > 0) {
            $value = str_replace($groupSymbol, '', $value);
        }

        $value = $value;
        $value += (float) $amount;

        // Re-add group symbols each $groups
        if ($groups > 0) {
            $value = str_split((string) $value, $groups);
            $value = implode($groupSymbol, $value);
        }

        return $value;
    }
}

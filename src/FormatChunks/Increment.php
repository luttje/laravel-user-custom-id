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

    /**
     * The largest (absolute) number the counter may reach, and the largest
     * step it may take. Beyond 14 digits PHP prints floats in scientific
     * notation with digits dropped, which would result in duplicate ids.
     */
    public const MAX_VALUE = 99_999_999_999_999;

    protected function validateParameters(): void
    {
        $id = static::getChunkId();
        $amount = (float) $this->getParameter('amount');

        if (! is_finite($amount) || $amount == 0 || abs($amount) > static::MAX_VALUE) {
            throw new \InvalidArgumentException("The parameter 'amount' for chunk type '{$id}' cannot be zero or larger than ".static::MAX_VALUE.'.');
        }

        if ((int) $this->getParameter('group-by') < 0) {
            throw new \InvalidArgumentException("The parameter 'group-by' for chunk type '{$id}' cannot be negative.");
        }

        // Digits in the symbol can't be told apart from the number itself.
        if (preg_match('/\d/', (string) $this->getParameter('group-symbol'))) {
            throw new \InvalidArgumentException("The parameter 'group-symbol' for chunk type '{$id}' cannot contain digits.");
        }
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

        $value += $amount;

        // Stop instead of handing out ids that are no longer unique.
        if (! is_finite($value) || abs($value) > static::MAX_VALUE) {
            throw new \OverflowException("The chunk type '".static::getChunkId()."' cannot count beyond ".static::MAX_VALUE.'.');
        }

        // Re-add group symbols each $groups
        if ($groups > 0) {
            $value = str_split((string) $value, $groups);
            $value = implode($groupSymbol, $value);
        }

        return $value;
    }
}

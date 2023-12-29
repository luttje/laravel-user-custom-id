<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Support\Collection;

/**
 * @template TValue of FormatChunk
 *
 * @extends Collection<int, TValue>
 */
class FormatChunkCollection extends Collection
{
    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        $string = '';

        foreach ($this->items as $item) {
            $string .= $item->getValue();
        }

        return $string;
    }
}

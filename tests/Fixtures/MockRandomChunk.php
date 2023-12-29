<?php

namespace Luttje\UserCustomId\Tests\Fixtures;

use Luttje\UserCustomId\FormatChunks\Random;

final class MockRandomChunk extends Random
{
    public static array $sequence = [];
    public static int $sequenceIndex = 0;

    public function getRandomNumber(int $min, int $max): int
    {
        // Get the next sequence item, wrapping around if necessary
        $value = self::$sequence[self::$sequenceIndex];

        self::$sequenceIndex++;

        if (self::$sequenceIndex >= count(self::$sequence)) {
            self::$sequenceIndex = 0;
        }

        return $value;
    }
}

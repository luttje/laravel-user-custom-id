<?php

namespace Luttje\UserCustomId\FormatChunks;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid as UuidGenerator;

class Uuid extends FormatChunk
{
    public static function getChunkId(): string
    {
        return 'uuid';
    }

    public static function getParametersConfig(): array
    {
        return [
            new FormatChunkParameter('version', 'integer', 4),
        ];
    }

    public function getNextValue(Model $target, Model $owner): mixed
    {
        $validVersions = [1, 4];
        $version = (int) $this->getParameter('version');

        if (! in_array($version, $validVersions)) {
            $version = 4;
        }

        return match ($version) {
            1 => UuidGenerator::uuid1()->toString(),
            default => UuidGenerator::uuid4()->toString(),
        };
    }
}

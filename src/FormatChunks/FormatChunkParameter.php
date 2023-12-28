<?php

namespace Luttje\UserCustomId\FormatChunks;

class FormatChunkParameter
{
    public function __construct(
        protected string $name,
        protected string $type,
        protected mixed $defaultValue = null,
    )
    { }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type ?? 'string';
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue ?? null;
    }
}

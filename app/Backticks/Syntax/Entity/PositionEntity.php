<?php

namespace App\Backticks\Syntax\Entity;

class PositionEntity
{
    public int $delta = 0;

    public function __construct(
        public string $name,
        public int $originalPosition,
        public int $originalLength,
        public int $replacedPosition,
        public int $replacedLength,
        public ?int $line = null,
    ) {
        $this->delta = $this->replacedLength - $this->originalLength;
    }
}

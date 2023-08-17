<?php

namespace App\Backticks\Syntax\Entity;

abstract class SyntaxEntity
{
    public function __construct(
        public string $raw,
        public string $value,
        public string $name,
        public ?PositionEntity $positionEntity = null,
    ) {
        $this->evaluate();
    }

    public function getPos(): int
    {
        return $this->positionEntity?->originalPosition ?? 0;
    }

    protected function evaluate()
    {
    }
}

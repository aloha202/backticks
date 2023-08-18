<?php

namespace App\Backticks\Syntax\Command;

use App\Backticks\Syntax\Entity\Command;
use App\Backticks\Syntax\Entity\PositionEntity;

abstract class AbstractCommandPart
{
    public string $value;
    public int $trimOffset;
    public function __construct(
        public string $rawValue,
        public Command $command,
        public ?PositionEntity $positionEntity = null,
    )
    {
        $this->value = trim($this->rawValue);
        $this->trimOffset = strpos($this->rawValue, $this->value);
        $this->evaluate();
    }

    public function getPos(): int
    {
        return ($this->positionEntity?->originalPosition ?? 0) + $this->trimOffset;
    }

    public function getFullPos(): int
    {
        return $this->command->getFullPos() + $this->getPos();
    }

    abstract protected function evaluate(): void;

    abstract protected function validate(): void;
}

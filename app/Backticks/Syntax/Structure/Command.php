<?php

namespace App\Backticks\Syntax\Structure;

use App\Backticks\Syntax\Entity\PositionEntity;
use App\Backticks\Syntax\Entity\StructureEntity;

class Command
{
    public string $value;
    public ?int $pos = null;
    public int $trimOffset;
    public function __construct(
        public string          $rawValue,
        public ?PositionEntity $positionEntity = null,
        public ?StructureEntity $structure = null,
    ) {
        $this->value = trim($this->rawValue);
        $this->trimOffset = strpos($this->rawValue, $this->value);
    }

    public function getPos(): int
    {
        return ($this->positionEntity?->originalPosition ?? 0) + $this->trimOffset;
    }

    public function getFullPos(): int
    {
        return $this->getPos() + ($this->structure?->getPos() ?? 0) + ($this->structure?->getLeftOffset() ?? 0);
    }
}

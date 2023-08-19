<?php

namespace App\Backticks\Syntax\Entity;

use App\Backticks\Syntax\Command\AbstractCommandPart;
use App\Backticks\Syntax\Command\Head;
use App\Backticks\Syntax\Command\Method;

class Command
{
    public string $value;
    public ?int $pos = null;
    public int $trimOffset;
    /**
     * @var array<AbstractCommandPart>
     */
    public array $parts = [];
    public Head $head;
    /**
     * @var array<Method>
     */
    public array $methods = [];
    public function __construct(
        public string          $rawValue,
        public ?PositionEntity $positionEntity = null,
        public ?StructureEntity $structure = null,
        public ?SubstructureEntity $subStructure = null,
        public ?ConditionalGroupEntity $groupEntity = null,
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
        return $this->getPos() + ($this->structure?->getPos() ?? 0) + ($this->structure?->getLeftOffset() ?? 0)
            + ($this->subStructure?->getFullPos() ?? 0)
            + ($this->groupEntity?->getFullPos() ?? 0)
        + ($this->groupEntity?->getTrimOffset() ?? 0);
    }

    public function isConditional()
    {
        return $this instanceof Conditional;
    }
}

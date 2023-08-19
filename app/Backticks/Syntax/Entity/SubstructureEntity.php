<?php

namespace App\Backticks\Syntax\Entity;

use App\Backticks\Syntax\Entity\Command;
use App\Backticks\Syntax\Entity\Conditional;

class SubstructureEntity extends SyntaxEntity
{
    public ?StructureEntity $structure = null;

    public Command $_command;

    public function getFullPos(): int
    {
        return $this->getPos() + ($this->structure?->getPos() ?? 0) + ($this->structure?->getLeftOffset() ?? 0);
    }

    protected function evaluate()
    {
    }

    public function getLeftOffset()
    {
        return 1;
    }
}

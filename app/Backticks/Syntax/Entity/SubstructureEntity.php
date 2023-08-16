<?php

namespace App\Backticks\Syntax\Entity;

class SubstructureEntity extends SyntaxEntity
{
    public ?StructureEntity $structure = null;

    public function getFullPos(): int
    {
        return $this->getPos() + ($this->structure?->getPos() ?? 0) + ($this->structure?->getLeftOffset() ?? 0);
    }
}

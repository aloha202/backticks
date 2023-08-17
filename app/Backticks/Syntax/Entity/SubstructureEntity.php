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
        parent::evaluate();

        $this->_command = Conditional::isConditionalValue($this->value) ?
            new Conditional($this->value) :
            new Command($this->value);
        $this->_command->subStructure = $this;
        if (null !== $this->positionEntity) {
            $this->_command->positionEntity = new PositionEntity(
                $this->value,
                1,
                $this->positionEntity->originalLength - ($this->getLeftOffset() * 2),
                1,
                $this->positionEntity->originalLength - ($this->getLeftOffset() * 2),
            );
        }

    }

    public function getLeftOffset()
    {
        return 1;
    }
}

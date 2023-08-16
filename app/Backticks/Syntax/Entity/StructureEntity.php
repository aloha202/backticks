<?php

namespace App\Backticks\Syntax\Entity;

class StructureEntity extends SyntaxEntity
{
    public array $_commands = [];

    public int $level = 1;

    public array $_substructures = [];

    public string $preparedValue;

    public function __construct(string $raw, string $value, string $name, ?PositionEntity $positionEntity = null)
    {
        parent::__construct($raw, $value, $name, $positionEntity);

        $this->preparedValue = $this->value;
    }

    public function getLeftOffset()
    {
        return 2 * $this->level;
    }
}

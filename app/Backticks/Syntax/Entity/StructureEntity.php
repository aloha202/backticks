<?php

namespace App\Backticks\Syntax\Entity;

class StructureEntity extends SyntaxEntity
{
    public array $_commands = [];

    public int $level = 1;

    public function getLeftOffset()
    {
        return 2 * $this->level;
    }
}

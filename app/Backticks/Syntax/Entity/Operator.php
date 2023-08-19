<?php

namespace App\Backticks\Syntax\Entity;

class Operator extends SyntaxEntity
{
    public function isConditional(): bool
    {
        return true;
    }
}

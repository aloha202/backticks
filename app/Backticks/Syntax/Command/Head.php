<?php

namespace App\Backticks\Syntax\Command;

use App\Backticks\Syntax\Exceptions\InvalidCommandHeadException;

class Head extends AbstractCommandPart
{
    protected function evaluate(): void
    {
        $this->validate();
    }

    protected function validate(): void
    {
        if ($this->value === '') {
            throw new InvalidCommandHeadException("Command head can not be blank", $this->getFullPos());
        }
    }
}

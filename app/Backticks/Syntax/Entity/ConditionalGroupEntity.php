<?php

namespace App\Backticks\Syntax\Entity;

use App\Backticks\Syntax\Exceptions\EmptyConditionalGroupException;

class ConditionalGroupEntity extends SyntaxEntity
{
    public ?Conditional $conditional = null;

    public array $_parts = [];

    /**
     * @var array<Operator>
     */
    public array $_operators = [];

    /**
     * @var array<Command>
     */
    public array $_commands = [];

    public function getFullPos()
    {
        return $this->conditional?->getFullPos() + $this->getPos();
    }

    public function getTrimOffset()
    {
        return strpos($this->raw, $this->value);
    }

    protected function validate()
    {
        if ($this->value === '') {
            throw new EmptyConditionalGroupException("Empty conditional group", $this->getFullPos());
        }
    }

    protected function evaluate()
    {
        $this->validate();
    }
}

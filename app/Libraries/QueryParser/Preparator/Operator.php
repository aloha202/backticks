<?php

namespace App\Libraries\QueryParser\Preparator;

class Operator
{
    public $raw;
    public $name;
    public $value;
    public function __construct($raw, $name, $value)
    {
        $this->raw = $raw;
        $this->name = $name;
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }

    public function isMathematical()
    {
        return in_array($this->value, ['eq', 'eq2', 'gt', 'gte', 'lt', 'lte', 'ne', 'ne2']);
    }
}

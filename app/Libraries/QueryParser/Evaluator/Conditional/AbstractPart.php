<?php

namespace App\Libraries\QueryParser\Evaluator\Conditional;

abstract class AbstractPart
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var string|array|null
     */
    public $evaluated = null;
    public function __construct($value)
    {
        $this->value = trim($value);
    }

    abstract public function getRealValue(): string;
}

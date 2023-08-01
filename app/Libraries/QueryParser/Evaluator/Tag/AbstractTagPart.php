<?php

namespace App\Libraries\QueryParser\Evaluator\Tag;

use App\Libraries\QueryParser\Preparator\OperatorExtractor;
use App\Libraries\QueryParser\Preparator\StringExtractor;
use App\Libraries\QueryParser\QueryPreparator;

abstract class AbstractTagPart
{
    public $value;

    /**
     * @var QueryPreparator|OperatorExtractor|StringExtractor|null
     */
    protected $preparator = null;

    public function __construct($value, $preparator = null)
    {
        $this->value = $value;

        $this->preparator = $preparator;

        $this->evaluate();
    }

    abstract public function evaluate();

    public function __toString()
    {
        return $this->value;
    }
}

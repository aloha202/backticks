<?php

namespace App\Libraries\QueryParser\Evaluator\Conditional;

class Right extends AbstractPart
{
    public function getRealValue(): string
    {
        return trim(preg_replace("/^\{|\}$/", '', $this->value));
    }
}

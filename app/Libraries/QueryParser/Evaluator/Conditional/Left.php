<?php

namespace App\Libraries\QueryParser\Evaluator\Conditional;

class Left extends AbstractPart
{
    public function getRealValue(): string
    {
        return preg_replace("/^\(|\)$/", '', $this->value);
    }
}

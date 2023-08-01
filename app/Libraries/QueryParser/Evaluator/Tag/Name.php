<?php

namespace App\Libraries\QueryParser\Evaluator\Tag;

use App\Libraries\QueryParser\Exception\InvalidTagNameException;

class Name extends AbstractTagPart
{
    public function evaluate()
    {
        $this->value = trim($this->value);
        if ($this->isInvalidTagName()) {
            throw new InvalidTagNameException("Invalid tag name '{$this->value}'");
        }

    }
    protected function isInvalidTagName()
    {
        return $this->value === '' ||
            preg_match('/^[a-zA-Z0-9_]+$/', $this->value) == false;
    }
}

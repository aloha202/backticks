<?php

namespace App\Libraries\QueryParser;

class Tag extends AbstractTag {
    /**
     * @var ?Conditional $conditional
     */
    public $conditional = null;

    public function isConditionalTag(): bool
    {
        return $this->conditional !== null;
    }
}

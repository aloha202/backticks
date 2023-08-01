<?php

namespace App\Libraries\QueryParser;

use App\Libraries\QueryParser;

class Conditional extends AbstractTag {

    public $index;
    public $tagValue;
    public $tags = [];

    public function __construct($raw, $value, $index)
    {
        parent::__construct($raw, trim($value));

        $this->index = $index;
        $this->tagValue = sprintf("<%s%s>", QueryParser::CONDITIONAL_TAG, $index);
    }
}

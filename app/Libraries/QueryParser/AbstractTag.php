<?php

namespace App\Libraries\QueryParser;

abstract class AbstractTag
{
    public $raw;
    public $value;
    public $computed = '';
    public function __construct($raw, $value) {
        $this->raw = $raw;
        $this->value = $value;
    }
}

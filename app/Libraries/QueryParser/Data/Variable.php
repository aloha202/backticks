<?php

namespace App\Libraries\QueryParser\Data;

class Variable
{
    public const PREG_PATTERN = '/@[\w]+/';

    public function __construct(public string $value)
    {}


}

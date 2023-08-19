<?php

namespace App\Backticks\Syntax\DTO;

class ConditionalParserConfig
{
    public function __construct(
        public string $leftHash = '0c478a8b11060e215b94f4b1984692a8',
        public string $rightHash = '91418d9bf2e6765cf0aa604a8c6d9f72',
        public string $leftAdditional = '+++++++++++++++++',
        public string $rightAdditional = '+++++++++++++++++',
    )
    {
    }
}

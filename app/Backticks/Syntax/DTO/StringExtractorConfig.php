<?php

namespace App\Backticks\Syntax\DTO;

class StringExtractorConfig
{
    public function __construct(
        public string $leftHash = '8f6df519a2125946820bc34a561164c2',
        public string $rightHash = '79a2520f22b9e1526ff93176029603b8',
        public string $leftAdditional = '++++++++',
        public string $rightAdditional = '++++++++',
    )
    {
    }
}

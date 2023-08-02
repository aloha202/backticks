<?php

namespace App\Backticks\Syntax\DTO;

class StructureExtractorConfig
{
    public function __construct(
        public string $leftHash = '6168789ba3d63060967c532982687cf6',
        public string $rightHash = 'e8c99a819fab5aef7609ace95572b88c',
        public string $leftAdditional = '++++++++++',
        public string $rightAdditional = '++++++++++',
    )
    {
    }
}

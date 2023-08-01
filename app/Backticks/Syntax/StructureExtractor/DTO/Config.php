<?php

namespace App\Backticks\Syntax\StructureExtractor\DTO;

class Config
{
    public function __construct(
        public string $structureLeftHash = '6168789ba3d63060967c532982687cf6',
        public string $structureRightHash = 'e8c99a819fab5aef7609ace95572b88c',
        public string $structureLeftAdditional = '++++++++++',
        public string $structureRightAdditional = '++++++++++',
    )
    {
    }
}

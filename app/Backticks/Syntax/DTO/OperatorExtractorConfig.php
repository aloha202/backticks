<?php

namespace App\Backticks\Syntax\DTO;

class OperatorExtractorConfig
{
    public function __construct(
        public string $leftHash = 'db075afc5dce4fd5b50316af3b0cd38f',
        public string $rightHash = 'b69ed2e9fc927fc31b0bb3a5313079c9',
        public string $leftAdditional = '++++++',
        public string $rightAdditional = '++++++',
    )
    {
    }
}

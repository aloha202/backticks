<?php

namespace App\Backticks\Syntax\DTO;

class SubstructureExtractorConfig
{
    public function __construct(
        public string $leftHash = '78cdeac478e1ba62b564cfc57b945f87',
        public string $rightHash = '2fec392304a5c23ac138da22847f9b7c',
        public string $leftAdditional = '+++++++++',
        public string $rightAdditional = '+++++++++',
    )
    {
    }
}

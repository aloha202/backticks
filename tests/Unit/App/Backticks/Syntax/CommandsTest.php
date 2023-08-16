<?php

namespace App\Backticks\Syntax;

use PHPUnit\Framework\TestCase;

class CommandsTest extends TestCase
{
    protected $preprocessor;
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->preprocessor = new Preprocessor(
            new StringExtractor(),
            new StructureExtractor(),
            new LineParser(),
            new PositionManager(),
            new StructureParser(),
        );
    }
}

<?php

namespace App\Backticks\Syntax;

use PHPUnit\Framework\TestCase;

class ConditionalParserWithPreprocessorTest extends TestCase
{
    protected $preprocessor;
    public function __construct(string $name)
    {
        parent::__construct($name);

        $pm = new PositionManager();
        $oe = new OperatorExtractor(null, $pm);
        $cp = new CommandParser($pm);
        $condP = new ConditionalParser($oe, null, $pm, $cp);
        $lp = new LineParser();
        $se = new SubstructureExtractor(null, $pm, $oe, $condP);
        $this->preprocessor = new Preprocessor(
            new StringExtractor(null, $lp, $pm),
            new StructureExtractor(null,$lp, $pm, $se),
            $lp,
            $pm,
            new StructureParser(),
            $oe,
            $condP,
        );

    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_parse
     */
    public function test_parse($input, $expected)
    {
        $input = $this->preprocessor->prepare($input);
        $this->preprocessor->parse();

        $this->assertEquals(1, $expected);
    }

    public static function data_parse()
    {
        return [
            ["`~ ~`", 1],
        ];
    }
}

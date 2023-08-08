<?php

namespace App\Backticks\Syntax;

use PHPUnit\Framework\TestCase;

class LineParserTest extends TestCase
{

    protected LineParser $lineParser;
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->lineParser = new LineParser();
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_lines
     */
    public function test_lines($input, $expected)
    {
        $this->lineParser->parse($input);
        $lines = [];
        foreach(['X', 'Y', 'Z'] as $key) {
            $pos = strpos($input, $key);
            if ($pos !== false) {
                $lines[] = $this->lineParser->getLine($pos);
            }
        }

        $this->assertEquals($expected, $lines);
    }

    public static function data_lines()
    {
        return [
            ["", []],
            [" X
            ", [0]],
            [" X
            Y", [0, 1]],
            [" X

            Y

            Z", [0, 2, 4]],
            ["X

Y

Z", [0, 2, 4]],
            ["X
Y
Z", [0, 1, 2]],
            ["
            X


                Y


sdfsdfaZ", [1, 4, 7]],
            ["


                XY


sdfsdfaZ", [3, 3, 6]],
            ["XYZ

            ", [0,0,0]],
        ];
    }
}

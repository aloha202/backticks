<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\DTO\StringExtractorConfig;
use App\Backticks\Syntax\Entity\StringEntity;
use App\Backticks\Syntax\Exceptions\ParseErrorException;
use PHPUnit\Framework\TestCase;

class StringExtractorTest extends TestCase
{
    protected StringExtractor $stringExtractor;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->stringExtractor = new StringExtractor();
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_entities_positions
     */
    public function test_entities_positions($input, $expected)
    {
        $this->stringExtractor->setPositionManager(new PositionManager());
        $this->stringExtractor->extractStrings($input);

        $this->assertEquals($expected, array_map(function(StringEntity $entity){
            return $entity->positionEntity->originalPosition;
        }, $this->stringExtractor->getEntities()));
    }

    public static function data_entities_positions()
    {
        return [
            ["'hello' mister 'well-behaved'",
            [0, 15]],
            ["'hello' mister 'well-behaved'''",
                [0, 15, 29]],
            ["'hello' mister 'well-behaved''' ds s d s d s d s d' dfds dfds'fdsdfdsdsdffd85 4 3 3'\''",
                [0, 15, 29, 50, 83]],
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_entities_position_exception
     */
    public function test_entities_position_exception($input, $expected)
    {
        $this->stringExtractor->setPositionManager(new PositionManager());
        $this->expectException(ParseErrorException::class);
        try {
            $this->stringExtractor->extractStrings($input);
        } catch (ParseErrorException $e) {
            $this->assertEquals($expected, $e->getPosition());
            throw $e;
        }
    }

    public static function data_entities_position_exception()
    {
        return [
            [
                "'hello' mister 'well-behaved''' ds s d s d s d s d' dfds dfds'fdsdfdsdsdffd85 4 3 3'\'' ' ",
                88
            ],
            [
                "'",
                0
            ]
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_matches
     */
    public function test_matches($input, $expected)
    {
        $matches = $this->stringExtractor->matchStrings($input);
        $this->assertEquals($expected, $matches[1]);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_there_and_back_again
     */
    public function test_there_and_back_again($input, $expected)
    {
        $result = $this->stringExtractor->extractStrings($input);
        $result = $this->stringExtractor->replaceBack($result, true);
        $this->assertEquals($input, $result);
    }

    public static function data_there_and_back_again()
    {
        $mapper = function ($item) {
            return $item[0];
        };
        $data = array_unique(array_merge(

            array_map($mapper, self::data_replace()),
            array_map($mapper, self::data_extract()),
            array_map($mapper, self::data_matches()),

        ));

        return array_map(function($item){return [$item, null];}, $data);

    }

    public static function data_matches()
    {
        return [
            ["
            ' '
            'hello'
            ", [' ', 'hello']],

            ["    ''''   ", ['', '']],
            ["    '\n''\n\n'   ", ["\n", "\n\n"]],
            ["    '\n''\n\n'   ", ["\n", "\n\n"]],
            ["    '\'''\'\n'   ", ["\'", "\'\n"]],
            ["'\''", ["\'"]],
            ["'\\''", ["\'"]],
            /*
            ["'\\\\''", ["\\\\'"]],
            this test fails, some combinations of \\\\' don't get evaluated properly
            eg. 5,6 works, 7,8 don't, then 9,10 again works - weird!
            */
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_extract
     */
    public function test_extract($input, $expected)
    {
        $this->stringExtractor->setConfig(new StringExtractorConfig(
            '',
            '',
            '[',
            ']',
        ));
        $result = $this->stringExtractor->extractStrings($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_replace
     */
    public function test_replace($input, $expected)
    {
        $result = $this->stringExtractor->extractStrings($input);
        $result = $this->stringExtractor->replaceBack($result);
        $this->assertEquals($expected, $result);
    }

    public static function data_replace()
    {
        return [
            ["''", ""],
            ["''''", ""],
            ["'\''", "'"],
            ["'\'\''", "''"],
            [" 'hello', world''", " hello, world"],
            [" '\n'", " \n"],
            ["'\ ' '\\''", "\  '"]
        ];
    }

    public static function data_extract() {
        return [
            ["''", "[1]"],
            ["
            '                '
            ", "
            [1]
            "],
            ["
            '                ' & '''\\''
            ", "
            [1] & [2][3]
            "],
            ["
            '        hello \'    \'   \'\'\'\'\'\'\'\'\\'        ' & '\'''\''
            ", "
            [1] & [2][3]
            "],
            ["
            '        hello \'    \'   \'\'\'\'\'\'\'\'\\'\"  \\\\\\\\\\
                ' & '\'''\''
            ", "
            [1] & [2][3]
            "],
            ["
            ' ' & '''\\'
            ' ' ' '[]'
            ", "
            [1] & [2][3] [4] [5]
            "],

        ];
    }

    /**
     * @param $input
     * @param $expected
     * @throws Exceptions\ParseErrorException
     * @dataProvider data_exceptions
     */
    public function test_exceptions($input, $expected)
    {
        $this->expectException($expected);
        $this->stringExtractor->extractStrings($input);
    }

    public static function data_exceptions() {
        return [
            ["'''", ParseErrorException::class],
            ["'", ParseErrorException::class],
            ["'      fsf as dfa
            ' sdfasdfasdf sd fsadf asd fa ' sdfas dfs df asd fasdf asdf
            sdf sadfsadfas df sd", ParseErrorException::class],
        ];
    }
}

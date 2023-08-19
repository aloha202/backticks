<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Entity\Operator;
use PHPUnit\Framework\TestCase;

class OperatorExtractorTest extends TestCase
{
    protected $operatorExtractor;
    protected $preprocessor;
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->operatorExtractor = new OperatorExtractor(
            null,
            new PositionManager(),
        );

        $this->preprocessor = new Preprocessor(
            new StringExtractor(),
            new StructureExtractor(),
            new LineParser(),
            new PositionManager(),
        );
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_values
     */
    public function test_values($input, $expected)
    {
        $this->operatorExtractor->extractOperators($input);
        $operators = array_map(function (Operator $op) {
            return $op->value;
        }, $this->operatorExtractor->getEntities());

        $this->assertEquals($expected, $operators);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_positions
     */
    public function test_positions($input, $expected)
    {
        $this->operatorExtractor->extractOperators($input);
        $operators = array_map(function (Operator $op) {
            return $op->getPos();
        }, $this->operatorExtractor->getEntities());

        $this->assertEquals($expected, $operators);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_full_parse
     */
    public function test_full_parse($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $positions = array_map(function (Operator $operator){
            return $this->preprocessor->getLineAndPositionInLine($operator->getPos());
        }, $this->preprocessor->getOperatorEntities());

        $this->assertEquals($expected, $positions);
    }

    public static function data_full_parse()
    {
        return [
            [" `~  test | if : `1 ~<>~ 2 `  ~`", [[0, 20]]],
        ];
    }

    public static function data_positions()
    {
        return [
            ["test <> or hello ~or~", [5, 17]],
            ["test != or hello ~lte~~or~ ", [5, 17, 22]],
        ];
    }

    public static function data_values()
    {
        return [
            [" test ~<>~ and hello ~=~
            or ~or~  and then ~and~", ['ne', 'eq', 'or', 'and']],

            [" test != and hello ~eq~
            or &&  and then >=", ['ne', 'eq', 'and', 'gte']],
        ];
    }
}

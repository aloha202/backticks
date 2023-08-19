<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\DTO\ConditionalParserConfig;
use App\Backticks\Syntax\Entity\Command;
use App\Backticks\Syntax\Entity\Conditional;
use App\Backticks\Syntax\Entity\ConditionalGroupEntity;
use App\Backticks\Syntax\Entity\Operator;
use App\Backticks\Syntax\Exceptions\BackticksSyntaxErrorException;
use App\Backticks\Syntax\Exceptions\EmptyConditionalGroupException;
use PHPUnit\Framework\TestCase;

class ConditionalParserTest extends TestCase
{
    protected $conditionalParser;
    protected $operatorExtractor;
    protected $pm;
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->pm = new PositionManager();
        $this->operatorExtractor = new OperatorExtractor(null, $this->pm);
        $this->conditionalParser = new ConditionalParser(
            $this->operatorExtractor,
            null,
            $this->pm,
            new CommandParser($this->pm),
        );
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_split
     */
    public function test_split($input, $expected)
    {
        $input = $this->operatorExtractor->extractOperators($input);
        $result = $this->conditionalParser->splitGroup($input);

        $this->assertEquals($expected, array_map(function ($item) {
            return $item instanceof Operator ? $item->value : trim($item);
        }, $result));
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_match_braces
     */
    public function test_match_braces($input, $expected)
    {
        $result = $this->conditionalParser->matchRoundBraces($input);
        $this->assertEquals($expected, $result[1]);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_extract_groups
     */
    public function test_extract_groups($input, $expected)
    {
        $this->conditionalParser->setConfig(new ConditionalParserConfig(
            '{', '}', '', ''
        ));
        $conditional = $this->conditionalParser->replaceGroups(new Conditional($input));
        $this->assertEquals($expected, $conditional->replacedValue);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_parse_group_num
     */
    public function test_parse_groups_num($input, $expected)
    {
        $input = $this->operatorExtractor->extractOperators($input);
        $conditional = $this->conditionalParser->parse(new Conditional($input));

        $this->assertEquals($expected, count($conditional->_groups));
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_parse_commands_num
     */
    public function test_parse_commands_num($input, $expected)
    {
        $input = $this->operatorExtractor->extractOperators($input);
        $conditional = $this->conditionalParser->parse(new Conditional($input));

        $this->assertEquals($expected, count($conditional->_commands));
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_parse_group_positions
     */
    public function test_parse_groups_positions($input, $expected)
    {
        $input = $this->operatorExtractor->extractOperators($input);
        $conditional = $this->conditionalParser->parse(new Conditional($input));

        $positions = array_map(function(ConditionalGroupEntity $group){
            return $group->getFullPos();
        }, $conditional->_groups);
        sort($positions);
        $this->assertEquals($expected, $positions);
    }

    public static function data_parse_group_positions()
    {
        return [
            ["1 = 2 && 2 = 3", []],
            ["(((1 = 2 && 2 = 3)))", [0, 1, 2]],
            ["(((1 = 2 && 2 = 3))) ~or~ (test = 12) || (12)", [0, 1, 2, 26, 41]],
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_parse_commands_positions
     */
    public function test_parse_commands_positions($input, $expected)
    {
        $input = $this->operatorExtractor->extractOperators($input);
        $conditional = $this->conditionalParser->parse(new Conditional($input));

        $positions = array_map(function(Command $command){
            return $command->getFullPos();
        }, $conditional->_commands);
        sort($positions);
        $this->assertEquals($expected, $positions);
    }


    /**
     * @param $input
     * @param $expected
     * @dataProvider data_parse_parts
     */
    public function test_parse_parts($input, $expected)
    {
        $input = $this->operatorExtractor->extractOperators($input);
        $conditional = $this->conditionalParser->parse(new Conditional($input));

        $parts = $this->_recursiveMapParts($conditional->getAllParts());
        $this->assertEquals($expected, $parts);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_exceptions
     */
    public function test_exceptions($input, $expected)
    {
        $this->expectException($expected);
        $input = $this->operatorExtractor->extractOperators($input);
        $this->conditionalParser->parse(new Conditional($input));
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_exception_positions
     */
    public function test_exception_positions($input, $expected)
    {
        try {
            $input = $this->operatorExtractor->extractOperators($input);
            $this->conditionalParser->parse(new Conditional($input));
        } catch (BackticksSyntaxErrorException $e) {
            $this->assertEquals($expected, $e->getPosition());
        }
    }

    public static  function data_exception_positions()
    {
        return [
            ["() || 1", 0],
            ["(1 <> 2) || 1 ~||~ ( () )", 21],
        ];
    }

    public static function data_exceptions()
    {
        return [
            ["test <> 1 and ()", EmptyConditionalGroupException::class],
            ["test ~or~ 1 ~and~ ( () )", EmptyConditionalGroupException::class],
        ];
    }

    public static function data_parse_parts()
    {
        return [
            ["item = 12", [Command::class, Operator::class, Command::class]],
            ["item = 12 ~and~ test | hello = @world", [
                Command::class, Operator::class, Command::class, Operator::class, Command::class, Operator::class, Command::class]
            ],

            ["item = 12 ~or~ (test = 1)", [
                Command::class, Operator::class, Command::class, Operator::class, [Command::class, Operator::class, Command::class]
            ]],

            ["(item = 12 ~or~ (test = 1))", [
                [Command::class, Operator::class, Command::class, Operator::class, [Command::class, Operator::class, Command::class]]
            ]],
        ];
    }

    public static function data_parse_commands_positions()
    {
        return [
            ["1 = 2 && 2 = 3", [0, 4, 9, 13]],
            ["(1 = 2) && (2 = 3)", [1, 5, 12, 16]],
            ["((1 = 2) && (2 = 3))", [2, 6, 13, 17]],
            ["(@test | mult: 1 ~=~ 1) ~and~ request | get", [1, 21, 30]],
        ];
    }

    public static function data_parse_commands_num()
    {
        return [
            ["(((1 = 2 && 2 = 3)))", 4],
            ["1 = 2 ~or~ (2 = 3 && test | mult : 2 = 11)", 6],
            ["z | x = @match | main: 88 ~or~ (21 | call: @func = 3 && test | mult : 2 = 11)", 6],
        ];
    }

    public static function data_parse_group_num()
    {
        return [
            ["1 = 2 && 2 = 3", 0],
            ["1 = 2 && (2 = 3)", 1],
            ["(1 = 2) && (2 = 3)", 2],
            ["((1 = 2) && (2 = 3))", 3],
            ["((1 = 2) && (2 = 3) ~or~ 2 < 3 | test)", 3],
            ["((1 = 2) && (2 = 3) ~or~ 2 < 3 | test) || (1 < 2 ~and~ 5 <> 6)", 4],
        ];
    }

    public static function data_extract_groups()
    {
        return [
            ["(group1) or (group2)", "{1} or {2}"],
            ["(group1 and (group3)) or (group2)", "{3} or {2}"],
            ["(group1 and (group3)) or (group2) or (group2)", "{4} or {2} or {3}"],
            ["(group1 or (subgroup or (one more))) and (last)", "{4} and {2}"],
        ];
    }

    public static function data_match_braces()
    {
        return [
            ["test (test) hello (one more) test", ['test', 'one more']],
            ["test (one more (test)) hello (one more) test", ['test', 'one more']],
            ["test (well done (stake())) hello (one more) test", ['', 'one more']],
        ];
    }

    public static function data_split()
    {
        return [
            ["hello < world", ['hello', 'lt', 'world']],
            ["hello < world && 1 > 2", ['hello', 'lt', 'world', 'and', '1', 'gt', '2']],
            ["hello ~=~ world ~or~ 1 ~lte~ 2", ['hello', 'eq', 'world', 'or', '1', 'lte', '2']],
            ["1 ~<>~ 2 ~or~ 3 != 4", ['1', 'ne', '2', 'or', '3', 'ne', '4']],
        ];
    }

    protected function _recursiveMapParts(array $array)
    {
        return array_map(function ($item) {
            if (is_array($item)) {
                return $this->_recursiveMapParts($item);
            }

            return get_class($item);
        }, $array);
    }
}

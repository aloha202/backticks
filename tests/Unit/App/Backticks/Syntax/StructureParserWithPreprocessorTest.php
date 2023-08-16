<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Entity\StructureEntity;
use App\Backticks\Syntax\Structure\Command;
use PHPUnit\Framework\TestCase;

class StructureParserWithPreprocessorTest extends TestCase
{
    protected $structureParser;
    protected $preprocessor;
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->structureParser = new StructureParser();
        $this->preprocessor = new Preprocessor(
            new StringExtractor(),
            new StructureExtractor(),
            new LineParser(),
            new PositionManager(),
        );
        $this->structureParser->setPositionManager($this->preprocessor->getPositionManager());
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_positions
     */
    public function test_positions($input, $expected)
    {
        $input = $this->preprocessor->prepare($input);
        $this->structureParser->parse(new StructureEntity($input, $input, $input));

        $result = array_map(function(Command $command) {
            return $command->positionEntity->originalPosition;
        }, $this->structureParser->_commands);

        $this->assertEquals($expected, $result);

    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_with_substructures
     */
    public function test_with_substructures($input, $expected)
    {
        $input = $this->preprocessor->prepare($input);
        $entity = new StructureEntity($input, $input, $input);
        $this->preprocessor->_prepareStructure($entity);
        $this->structureParser->parse($entity);

        $result = array_map(function(Command $command) {
            return $command->getPos();
        }, $this->structureParser->_commands);

        $this->assertEquals($expected, $result);
        $this->structureParser->clear();
        $this->preprocessor->clear();

    }

    public static function data_with_substructures()
    {
        return [
       //     [" test ~ full", [1, 8]],
            ["``''test ~ full", [0, 11]],
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_lengths
     */
    public function test_lengths($input, $expected)
    {
        $input = $this->preprocessor->prepare($input);
        $this->structureParser->parse(new StructureEntity($input, $input, $input));

        $result = array_map(function(Command $command) {
            return $command->positionEntity->originalLength;
        }, $this->structureParser->_commands);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_lines
     */
    public function test_lines($input, $expected)
    {
        $input = $this->preprocessor->prepare($input);
        $this->structureParser->parse(new StructureEntity($input, $input, $input));

        $result = array_map(function(Command $command) {
            return $this->preprocessor->getLine($command->positionEntity->originalPosition);
        }, $this->structureParser->_commands);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_lines_real
     */
    public function test_lines_real($input, $expected)
    {
        $input = $this->preprocessor->prepare($input);
        $this->structureParser->parse(new StructureEntity($input, $input, $input));

        $result = array_map(function(Command $command) {
            return $this->preprocessor->getLine($command->getFullPos());
        }, $this->structureParser->_commands);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_full_parse_positions
     */
    public function test_full_parse_positions($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $structures = $this->preprocessor->getStructureEntities(true);
        $positions = [];
        foreach($structures as $structureEntity) {
            $this->structureParser->parse($structureEntity);
            $positions = array_merge($positions, array_map(function(Command $command) use ($structureEntity) {
                return $command->getFullPos();
            }, $this->structureParser->_commands));
            $this->structureParser->clear();
        }

        sort($positions);

        $this->assertEquals($expected, $positions);
    }



    public static function data_full_parse_positions()
    {
        return [
            ["`~command1~`", [2]],
            ["`~command1~``~command2`~command3~`~`", [2, 14, 24]],
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_full_parse_lines_real
     */
    public function test_full_parse_lines_real($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $structures = $this->preprocessor->getStructureEntities(true);
        $positions = [];
        foreach($structures as $structureEntity) {
            $this->structureParser->parse($structureEntity);
            $positions = array_merge($positions, array_map(function(Command $command) {
                return $command->getFullPos();
            }, $this->structureParser->_commands));
            $this->structureParser->clear();
        }

        sort($positions);

        $result = array_map(function (int $pos) {
            return $this->preprocessor->getLine($pos);
        }, $positions);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_full_parse_lines
     */
    public function test_full_parse_lines($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $structures = $this->preprocessor->getStructureEntities(true);
        $positions = [];
        foreach($structures as $structureEntity) {
            $this->structureParser->parse($structureEntity);
            $positions = array_merge($positions, array_map(function(Command $command) use ($structureEntity) {
                return $command->positionEntity->originalPosition + $structureEntity->positionEntity->originalPosition;
            }, $this->structureParser->_commands));
            $this->structureParser->clear();
        }

        sort($positions);

        $result = array_map(function (int $pos) {
            return $this->preprocessor->getLine($pos);
        }, $positions);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_full_parse_lines_and_positions_in_lines_real
     */
    public function test_full_parse_lines_and_positions_in_lines_real($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $structures = $this->preprocessor->getStructureEntities(true);
        $positions = [];
        foreach($structures as $structureEntity) {
            $this->structureParser->parse($structureEntity);
            $positions = array_merge($positions, array_map(function(Command $command) {
                return $command->getFullPos();
            }, $this->structureParser->_commands));
            $this->structureParser->clear();
        }

        sort($positions);

        $result = array_map(function (int $pos) {
            return $this->preprocessor->getLineAndPositionInLine($pos);
        }, $positions);

        $this->assertEquals($expected, $result);
    }

    public static function data_full_parse_lines_and_positions_in_lines_real()
    {
        return [
            ["`~
com1 ~
com2 'a little string' : `~ com3 ~ com4 : `~
com5 ~ com6 `sub1` `sub2` `sub3`
~` | 'another multiline
string'
~` ~
`sub` com7: `~
com8: `~
' multiline string
for ' | com9 | plus: in backticks | do : `~
com10 ~ com11 ~ com12 : `~
com13
~ com14
~`
~`
~`
~` ~
some `sub` ' here comes a
nother multiline
string' that is for `sub2` com15 ~com16
            ~`", [[1, 0], [2, 0], [2, 28], [2, 35], [3, 0], [3, 7], [7, 0], [8, 0], [9, 0], [11, 0], [11, 8], [11, 16], [12, 0], [13, 2], [18, 0], [20, 34]]],
        ];
    }


    public static function data_full_parse_lines_real()
    {
        return [
            ["
`~
    command1
~
command2
            ~`", [2, 4]],

            ["
`~
    command1
~
command2
            ~`
            `~command3
~
command4~`", [2, 4, 6, 8]],

            ["
`~
    command1
            ~`", [2]],

            ["
            `~
       command1     ~ command2
            ~`", [2, 2]],

            ["
            `~
       command1
            ~``~ command2 ~`



            ", [2, 3]],

            ["
            `~
       command1
            ~``~
            command2
            `~
            command3
            ~`
            ~`



            ", [2, 4, 6]],

            ["
            `~
       command1
            ~``~
            command2
            `~
            `~
            'test string' ~
            command3
            ~`
            ~`
            ~`
            ", [2, 4, 6, 7, 8]],
            ["
            `~
       command1
            ~``~
            command2
            `~
            `~
            'test string'
~command3
            ~`
            ~`
            ~`

            `~
            one more
            ~`
            ", [2, 4, 6, 7, 8, 14]],

            ["`~`~`~ cmd ~`~`~`", [0, 0, 0]],
            ["`~`~`~ cmd ~`~`~``~
            ' stringy ' | len | pow : 2
            ~`", [0, 0, 0, 1]],

            ["`~
            def | params : @num | func : `~
                @num | mult : @num | return
            ~` | name : square ~
            10 | square
            ~`", [1, 2, 4]],

            ["`~
            10, 20, 30 | var : array ~
            100, 200, 300 | var : array2 ~
            'String', 'Strin', 'Strg' | var : stringArray ~
            @stringArray | map : @index,@item : `~
                @item | concat : `~ @array2 | get : @index ~` | concat : '-'| concat: `~ @array | get : @index ~` | return
            ~` ~
            @stringArray | join : ','
            ~`", [1, 2, 3, 4, 5, 5, 5, 7]],

            ["`~
            request | get : item | if : `@it|lowercase = 'test' and request|get : product = 12` | do: `~
                mysql | query : 'SELECT * FROM `table` WHERE `field`=GOOD' | pluck : name,age | var : @people
            ~` | else | do: `~ NULL | var: @people ~`
            ~`", [1, 2, 3]],

            ["`~

request | get : item | if : `@it|lowercase = 'test' and request|get : product = 12` | do: `~
    mysql | query : 'SELECT * FROM `table` WHERE `field`=GOOD' | pluck : name,age | var : @people
~` | else | do: `~ NULL | var: @people ~`
            ~`", [2, 3, 4]],

            ["`~
com1 ~
com2 'a little string' : `~ com3 ~ com4 : `~
com5 ~ com6
~` | 'another multiline
string'
~` ~
com7: `~
com8: `~
' multiline string
for ' | com9 | plus: `in backticks` | do : `~
com10 ~ com11 ~ com12 : `~
com13
~ com14
~`
~`
~`
~` ~
' here comes a
nother multiline
string' that is for com15 ~ com16
            ~`", [1, 2, 2, 2, 3, 3, 7, 8, 9, 11, 11, 11, 12, 13, 18, 20]],
        ];
    }

    public static function data_full_parse_lines()
    {
        return [
            ["
            `~
       command1     ~ command2
            ~`", [1, 2]],

            ["
            `~
       command1
            ~``~ command2 ~`



            ", [1, 3]],

            ["
            `~
       command1
            ~``~
            command2
            `~
            command3
            ~`
            ~`



            ", [1, 3, 5]],

            ["
            `~
command1
            ~``~
command2
`~
command3
            ~`
            ~`
            ", [1, 3, 5]],
        ];
    }

    public static function data_lines()
    {
        return [

            ["
~ hello
~
            ", [0, 1, 2]],

            ["
~ hello
~
~
            ", [0, 1, 2, 3]],

            ["
            ~
            ", [0, 1]],

            ["'
            '~
            ", [0, 1]],

            ["'first command


            '~ 'second command

            '
            ~ `~
third command | go : 1
            ~`
            ", [0, 3, 6]],

            ["'first command


            '~ 'second command

            '
            ~ `~
third command | go : 1
            ~` ~~
            ", [0, 3, 6, 8, 8]],

            ["

            ~
            `~
            `~
            ~`
            ~` ~
            ", [0, 2, 6]],
        ];
    }

    public static function data_lines_real()
    {
        return [
            ["
            command1~
            command2
            ", [1, 2]],

            ["
command1~
command2
            ", [1, 2]],

            ["
command1~
command2~

command3~command4
            ", [1, 2, 4, 4]],
        ];
    }

    public static function data_lengths()
    {
        return [
            [" ~ ", [1, 1]],
            ["''~ ", [2, 1]],
            ["''''", [4]],
            ["''~''''", [2,4]],
            ["''~''''~", [2,4, 0]],
            ["''~''''~ `~''~`", [2,4, 7]],
            ["''~''''~ `~''~` ~ `~`~`~~`~`~` ", [2,4, 8, 14]],
            ["''~''''~ `~''~` ~ `~`~`~ `` ~`~`~` ", [2,4, 8, 18]],
        ];
    }

    public static function data_positions()
    {
        return [
            [' ~ ', [0,2]],
            ["''~ ", [0,3]],
            ["'' ~ `~~` ~ ", [0,4, 11]],
            ["'' ~ `~''~` ~ ", [0,4, 13]],
            ["'' ~ `~`~''~`~` ~ ", [0,4, 17]],
        ];
    }
}

<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Command\AbstractCommandPart;
use App\Backticks\Syntax\Command\Head;
use App\Backticks\Syntax\Command\Method;
use App\Backticks\Syntax\Command\MethodParam;
use App\Backticks\Syntax\Entity\Command;
use App\Backticks\Syntax\Exceptions\BackticksSyntaxErrorException;
use App\Backticks\Syntax\Exceptions\InvalidCommandHeadException;
use App\Backticks\Syntax\Exceptions\InvalidMethodException;
use PHPUnit\Framework\TestCase;

class CommandParserTest extends TestCase
{
    protected $commandParser;
    protected $preprocessor;
    public function __construct(string $name)
    {
        parent::__construct($name);
        $pm = new PositionManager();
        $this->commandParser = new CommandParser();
        $this->commandParser->setPositionManager($pm);
        $this->preprocessor = new Preprocessor(
            new StringExtractor(),
            new StructureExtractor(),
            new LineParser(),
            $pm,
        );
        $this->preprocessor->setStructureParser(new StructureParser($pm, $this->commandParser));
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_parse
     */
    public function test_parse($input, $expected)
    {
        $command = $this->commandParser->parse(new Command($input));
        $result = array_map(function ($item) {
            return get_class($item);
        }, $command->parts);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_exceptions
     */
    public function test_exceptions($input, $expected)
    {
        $this->expectException($expected);
        $this->commandParser->parse(new Command($input));
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_positions
     */
    public function test_positions($input, $expected)
    {
        $this->commandParser->setPositionManager(new PositionManager());

        $command = $this->commandParser->parse(new Command($input));
        $positions = array_map(function (AbstractCommandPart $item) {
            return $item->getPos();
        }, $command->parts);

        $this->assertEquals($expected, $positions);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_method_params_positions
     */
    public function test_method_params_positions($input, $expected)
    {
        $this->commandParser->setPositionManager(new PositionManager());

        $command = $this->commandParser->parse(new Command($input));
        $params = [];
        foreach($command->methods as $method) {
            $params = array_merge($params, $method->parameters);
        }

        $positions = array_map(function (MethodParam $param) {
            return $param->getPos();
        }, $params);

        $this->assertEquals($expected, $positions);
    }

    public static function data_method_params_positions()
    {
        return [
            ["test | go : 1", [5]],
            ["test | go : 1 :  helo", [5, 10]],
            ["test | go : 1 :  helo | another : test : test ", [5, 10, 10, 17]],
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_method_params_full_positions
     */
    public function test_method_params_full_positions($input, $expected)
    {
        $this->commandParser->setPositionManager(new PositionManager());

        $command = $this->commandParser->parse(new Command($input));
        $params = [];
        foreach($command->methods as $method) {
            $params = array_merge($params, $method->parameters);
        }

        $positions = array_map(function (MethodParam $param) {
            return $param->getFullPos();
        }, $params);

        $this->assertEquals($expected, $positions);
    }

    public static function data_method_params_full_positions()
    {
        return [
            ["test | go : 1", [12]],
            ["test | go : 1 :  helo", [12, 17]],
            ["test | go : 1 :  helo | another : test : test ", [12, 17, 34, 41]],
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_full_parse_positions
     */
    public function test_full_parse_positions($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $this->preprocessor->parse();
        $positions = [];
        foreach($this->preprocessor->getStructureEntities(true) as $structureEntity) {
            foreach($structureEntity->_commands as $command) {
                foreach($command->parts as $part) {
                    $positions[] = $part->getFullPos();
                }
            }
        }

        $this->assertEquals($expected, $positions);

        $this->preprocessor->clear();
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_full_parse_positions_in_lines
     */
    public function test_full_parse_positions_in_lines($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $this->preprocessor->parse();
        $positions = [];
        foreach($this->preprocessor->getStructureEntities(true) as $structureEntity) {
            foreach($structureEntity->_commands as $command) {
                foreach($command->parts as $part) {
                    $positions[] = $part->getFullPos();
                }
            }
        }

        sort($positions);

        $positions = array_map(function (int $pos) {
            return $this->preprocessor->getLineAndPositionInLine($pos);
        }, $positions);

        $this->assertEquals($expected, $positions);

        $this->preprocessor->clear();
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_full_parse_params_positions_in_lines
     */
    public function test_full_parse_params_positions_in_lines($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $this->preprocessor->parse();
        $positions = [];
        foreach($this->preprocessor->getStructureEntities(true) as $structureEntity) {
            foreach($structureEntity->getAllCommands(true) as $command) {
                foreach($command->methods as $method) {
                    foreach($method->parameters as $param) {
                        $positions[] = $param->getFullPos();
                    }
                }
            }
        }

        sort($positions);

        $positions = array_map(function (int $pos) {
            return $this->preprocessor->getLineAndPositionInLine($pos);
        }, $positions);

        $this->assertEquals($expected, $positions);

        $this->preprocessor->clear();
    }

    public static function data_full_parse_params_positions_in_lines()
    {
        return [
            ["`~
            'te' | call : hello

            ~`",
               [
                   [1, 26]
               ]
            ],

            ["`~`~`~
            'te' | call : hello : `sub | test : '1' :
            2`

            ~`~`~`",
                [
                    [1, 26],
                    [1, 34],
                    [1, 48],
                    [2, 12],
                ]
            ],

            ["`~`~`~
            'te' | call : hello : `sub | test : '1' :
            2` : `~

            'test' | callee : `

            '21'` : `~ '123' ~`

            ~`

            ~`~`~`",
                [
                    [1, 26],
                    [1, 34],
                    [1, 48],
                    [2, 12],
                    [2, 17],
                    [4, 30],
                    [6, 20],
                ]
            ],

            ["`~`~`~ `~ noise | test ~`
            'te' | call : hello : `sub | test : '1' :
            2` : `~ 'multiline
-string noise'
            'test' | callee : `
'noise'
            '21'` : `~ '123' | concat : `'string'` ~`

            ~`

            ~`~`~`",
                [
                    [1, 26],
                    [1, 34],
                    [1, 48],
                    [2, 12],
                    [2, 17],
                    [4, 30],
                    [6, 20],
                    [6, 40],
                ]
            ],
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_full_parse_exceptions_positions
     */
    public function test_full_parse_exceptions_positions($input, $expected)
    {
        try {
            $this->preprocessor->prepare($input);
            $this->preprocessor->parse();
        } catch(BackticksSyntaxErrorException $e) {
            $this->assertEquals($expected, $this->preprocessor->getLineAndPositionInLine($e->getPosition()));
        }
    }

    public static function data_full_parse_exceptions_positions()
    {
        return [
            ["`~
            test | me*thod
            ~`", [1, 19]],
            ["`~
            test |
            ~`", [1, 18]],
            ["`~
            | hello
            ~`", [1, 12]],

            ["`~
            `~ omega |
            meth od ~`
            ~`", [2, 12]],

            ["`~
            `~  ~` | omega
            ~`", [1, 14]],

            ["`~
            test | concat:
            '' :
            `~  ~`
            | omega
            ~`", [3, 14]],

            ["`~
            test | concat:
            '' :
            `~ hello | ba&d ~`
            | omega
            ~`", [3, 23]],

            ["`~
            test | concat:
            '' :
            `~ 'valid' | 'invalid' ~`
            | omega
            ~`", [3, 25]],

            ["`~
            test | concat:
            '' :
            `~ `valid` | 'invalid' ~`
            | omega
            ~`", [3, 25]],

            ["`~
            test | concat:
            '' :
            `~ `~valid~` | 'invalid' ~`
            | omega
            ~`", [3, 27]],

            ["`~
            test | concat:
            '' :
            `~ `~'valid'~` | 'invalid' ~`
            | omega
            ~`", [3, 29]],
        ];
    }

    public static function data_full_parse_positions_in_lines()
    {
        return [
            ["`~ command1|method1 ~`", [[0, 3], [0, 12]]],
            ["`~ command1|method1 ~``~ test | hello~`", [[0, 3], [0, 12], [0, 25], [0, 32]]],
            ["  `~
            com1 | test ~
            com2 | test2 : 'string' | go
            ~`", [
                [1, 12],
                [1, 19],
                [2, 12],
                [2, 19],
                [2, 38],
            ]],
            ["  `~
            com1 | test ~
            com2 |do: `~ test ~` |
                test2 : 'string' |
                go : @paramont
            ~`", [
                [1, 12],
                [1, 19],
                [2, 12],
                [2, 18],
                [2, 25],
                [3, 16],
                [4, 16],
            ]],
            ["  `~
                `~
                    `~ test com | go ~`
                ~` | execute: 'hello' ~ mysql
            ~`", [
                [1, 16],
                [2, 20],
                [2, 23],
                [2, 34],
                [3, 21],
                [3, 40],
            ]],
            ["  `~
                `~
                   'start'
                ~` | execute: `~ test|do ~` ~
mysql
            ~`", [
                [1, 16],
                [2, 19],
                [3, 21],
                [3, 33],
                [3, 38],
                [4, 0]
            ]],
        ];
    }

    public static function data_full_parse_positions()
    {
        return [
            ["`~ command1|method1 ~`", [3, 12]],
            ["`~ command1|method1 ~``~ test | hello~`", [3, 12, 25, 32]],
            ["`~ command1|method1: '' ~``~ test | hello~`", [3, 12, 29, 36]],
            ["`~ command1|method1: `12` ~``~ test | hello~`", [3, 12, 31, 38]],
            ["`~ command1|method1: `''` ~``~ test | hello~`", [3, 12, 31, 38]],
        ];
    }

    public static function data_positions()
    {
        return [
            ["test | hello : 1", [0, 7]],
            ["test | hello : 1| hello : 1", [0, 7, 18]],
            ["test | hello : 1| hello : 1 | test", [0, 7, 18, 30]],
            ["test | hello : 1| hello : 1  \t |  \n test", [0, 7, 18, 36]],
        ];
    }

    public static function data_exceptions()
    {
        return [
            ["  ", InvalidCommandHeadException::class],
            [" hellllo | ", InvalidMethodException::class],
            [" hellllo | meth*od ", InvalidMethodException::class],
            [" hellllo | meth od ", InvalidMethodException::class],
        ];
    }

    public static function data_parse(){
        return [
            ["head | param : 1 | param : 2", [Head::class, Method::class, Method::class]],
            ["just the head", [Head::class]],
        ];
    }
}

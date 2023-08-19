<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Entity\SubstructureEntity;
use App\Backticks\Syntax\Exceptions\SubstructureParseErrorException;
use App\Backticks\Syntax\Entity\Command;
use PHPUnit\Framework\TestCase;

class SubstructureExtractorTest extends TestCase
{
    protected $substructureExtractor;
    protected $preprocessor;
    public function __construct(string $name)
    {
        parent::__construct($name);

        $pm = new PositionManager();
        $this->substructureExtractor = new SubstructureExtractor(
            null,
            $pm,
        );

        $this->preprocessor = new Preprocessor(
            new StringExtractor(),
            new StructureExtractor(),
            new LineParser(),
            $pm,
        );
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_matches
     */
    public function test_matches($input, $expected)
    {
        $matches = $this->substructureExtractor->extractMatches($input);

        $this->assertEquals($expected, $matches[1]);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_positions
     */
    public function test_positions($input, $expected)
    {
        $this->substructureExtractor->prepare($input);
        $result = array_map(function(SubstructureEntity $entity){
            return $entity->positionEntity->originalPosition;
        }, $this->substructureExtractor->getPreparedEntities());

        $this->assertEquals($expected, $result);
    }


    /**
     * @param $input
     * @param $expected
     * @dataProvider data_positions_full_parse
     */
    public function test_positions_full_parse($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $structures = $this->preprocessor->getStructureEntities(true);
        $positions = [];
        foreach($structures as $structureEntity) {
            $positions = array_merge($positions, array_map(function(SubstructureEntity $sub) {
                return $sub->positionEntity->originalPosition;
            }, $structureEntity->_substructures));
        }

        $this->assertEquals($expected, $positions);

        $this->preprocessor->clear();
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_full_positions_full_parse
     */
    public function test_full_positions_full_parse($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $structures = $this->preprocessor->getStructureEntities(true);
        $positions = [];
        foreach($structures as $structureEntity) {
            $positions = array_merge($positions, array_map(function(SubstructureEntity $sub) {
                return $sub->getFullPos();
            }, $structureEntity->_substructures));
        }

        sort($positions);

        $this->assertEquals($expected, $positions);

        $this->preprocessor->clear();
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_lines_full_parse
     */
    public function test_lines_full_parse($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $structures = $this->preprocessor->getStructureEntities(true);
        $positions = [];
        foreach($structures as $structureEntity) {
            $positions = array_merge($positions, array_map(function(SubstructureEntity $sub) {
                return $sub->getFullPos();
            }, $structureEntity->_substructures));
        }

        sort($positions);

        $linesAndPos = array_map(function (int $pos) {
            return $this->preprocessor->getLineAndPositionInLine($pos);
        }, $positions);

        $this->assertEquals($expected, $linesAndPos);

        $this->preprocessor->clear();
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_with_commands_full_parse
     */
    public function test_with_commands_full_parse($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $structures = $this->preprocessor->getStructureEntities(true);
        $positions = [];
        foreach($structures as $structureEntity) {
            $positions = array_merge($positions, array_map(function(SubstructureEntity $sub) {
                return $sub->_command->getFullPos();
            }, $structureEntity->_substructures));
        }

        sort($positions);

        $linesAndPos = array_map(function (int $pos) {
            return $this->preprocessor->getLineAndPositionInLine($pos);
        }, $positions);

        $this->assertEquals($expected, $linesAndPos);

        $this->preprocessor->clear();
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_conditional_or_command_full_parse
     */
    public function test_conditional_or_command_full_parse($input, $expected)
    {
        $this->preprocessor->prepare($input);
        $structures = $this->preprocessor->getStructureEntities(true);
        $all_subs = [];
        foreach($structures as $structureEntity) {
            $all_subs = array_merge($all_subs, $structureEntity->_substructures);
        }

        usort($all_subs, function (SubstructureEntity $a, SubstructureEntity $b) {
            return $a->getFullPos() - $b->getFullPos();
        });

        $result = array_map(function (SubstructureEntity $sub) {
            return $sub->_command->isConditional();
        }, $all_subs);

        $this->assertEquals($expected, $result);

        $this->preprocessor->clear();
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_exceptions
     */
    public function test_exceptions($input, $expected)
    {
        $this->expectException($expected);
        $this->substructureExtractor->prepare($input);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_exceptions_positions_full_parse
     */
    public function test_exceptions_positions_full_parse($input, $expected)
    {
        try {
            $this->preprocessor->prepare($input);
        } catch(SubstructureParseErrorException $e) {
            $this->assertEquals($expected, $this->preprocessor->getLineAndPositionInLine($e->getPosition()));
        }
    }

    public static function data_exceptions_positions_full_parse()
    {
        return [
            [" `~
            `one sub`
` unterminated
            ~`", [2, 0]],
            [" `~
                `command` `~ `,&& good` ` unterminado ~`
                `conditional >`
            ~` `~  '''' `command`
                `~
                    `~ ===== '' `=` ~`
                ~`
            ~`", [1, 40]],
        ];
    }

    public static function data_exceptions()
    {
        return [
            [" htello `substr1`  unterminated ` backtick", SubstructureParseErrorException::class],
        ];
    }


    public static function data_conditional_or_command_full_parse()
    {
        return [
            [" `~
                `command`
                `conditional >`
            ~`", [0, 1]],

            [" `~
                `command` `~ `,&& good` ~`
                `conditional >`
            ~`", [0, 1, 1]],

            [" `~
                `command` `~ `,&& good` ~`
                `conditional >`
            ~` `~  '''' `command`
                `~
                    `~ ===== '' ` = ` ~`
                ~`
            ~`", [0, 1, 1, 0, 1]],
        ];
    }

    public static function data_with_commands_full_parse() {
        return [
            [" `~
`command `
`command2`
            ~`
            ", [
                [1, 1],
                [2, 1]
            ]
            ],

            [" `~
`    command `
`     command2`
            ~`
            ", [
                [1, 5],
                [2, 6]
            ]
            ],

            [" `~
            ' this is multi
            line string
            '
            `    command `
            `~`~
`   ' '  command2`
~`~`
`command3``command4`''`
command5`
            ~`
            ", [
                [4, 17],
                [6, 4],
                [8, 1],
                [8, 11],
                [9, 0],
            ]
            ],
        ];
    }

    public static function data_lines_full_parse()
    {
        return [
            ["  `~ `1`
`1`
            ~`", [[0, 5], [1,0]]
            ],

            ["  `~ `1`
        `~
   `~~` `sub1`
        ~`
            ~`", [[0, 5], [2,8]]
            ],

            ["  `~ `1`
        `~
   `~ `2` ~` `sub1`
        ~`
            ~`", [[0, 5], [2,6], [2,13]]
            ],

            ["  `~ `1`
        `~
   `~ `2` ~` `sub1`
        ~`
            ~`

`~
`moresub`

`submore`

~`
            ", [[0, 5], [2,6], [2,13], [7,0], [9,0]]
            ],

            ["  `~ `1`
        `~
   `~ `2` ~` `sub1`
        ~`
            ~`

`~
`moresub`

`submore` : `~
        `~ 'multi
line'      `~ 'one more line' `~  noise   ~`
             `~   `~   noise 2~`      `~ 'noise' `~  noise  `~ noise  ~`  ~`   ~`
`onemore`
             ~`
             ~`
        ~`
~`

~`
            ", [[0, 5], [2,6], [2,13], [7,0], [9,0], [13,0]]
            ],
        ];
    }

    public static function data_full_positions_full_parse()
    {
        return [
            ["`~ `sub1` and `sub2` ~`", [3, 14]],

            ["`~ `sub1` `` `sub2` ~`", [3, 10, 13]],

            ["`~ ''`sub1` `` `sub2` ~`", [5, 12, 15]],

            ["`~ ''`sub1` `~~` `sub2` ~`", [5, 17]],

          ["`~ ''`sub1` `~ `sub3` ~` `sub2` ~`", [5, 15, 25 ]],

          ["`~ ''`sub1` `sub2` ~``~ ''`sub3` ~`", [5, 12, 26 ]],
            ["`~ `sub0` `~`~ ''`sub1` `sub2` ~`~`~``~ ''`sub3` ~`", [3, 17, 24, 42]],

        ];
    }

    public static function data_positions_full_parse()
    {
        return [
            ["`~ `sub1` and `sub2` ~`", [1, 12]],
            ["`~ `sub1` `` `sub2` ~`", [1, 8, 11]],
            ["`~ ''`sub1` `` `sub2` ~`", [3, 10, 13]],
            ["`~ ''`sub1` `~~` `sub2` ~`", [3, 15]],
            ["`~ ''`sub1` `~ `sub3` ~` `sub2` ~`", [3, 23, 1 ]],
            ["`~ ''`sub1` `sub2` ~``~ ''`sub3` ~`", [3, 10, 3 ]],
        ];
    }

    public static function data_positions()
    {
        return [
            ["`sub1` and `sub2`", [0, 11]],
            ["```sub1` and `sub2`", [0, 2, 13]],
        ];
    }

    public static function data_matches()
    {
        return [
            ["`sub1`  `sub2`", ['sub1', 'sub2']],
            ["`sub1````sub2`", ['sub1', '', 'sub2']]
        ];
    }
}

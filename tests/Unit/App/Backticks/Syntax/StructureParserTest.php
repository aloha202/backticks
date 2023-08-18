<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Entity\StructureEntity;
use App\Backticks\Syntax\Entity\Command;
use PHPUnit\Framework\TestCase;

class StructureParserTest extends TestCase
{
    protected StructureParser $structureParser;
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->structureParser = new StructureParser();
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_parse
     */
    public function test_parse($input, $expected)
    {
        $structure = $this->structureParser->parse(new StructureEntity($input, $input, $input));
        $result = array_map(function(Command $command) {
            return $command->value;
        }, $structure->_commands);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_positions
     */
    public function test_positions($input, $expected)
    {
        $this->structureParser->setPositionManager(new PositionManager());
        $structure = $this->structureParser->parse(new StructureEntity($input, $input, $input));
        $result = array_map(function(Command $command) {
            return $command->positionEntity->originalPosition;
        }, $structure->_commands);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_lines
     */
    public function test_lines($input, $expected)
    {
        $lineParser = new LineParser();
        $lineParser->parse($input);
        $this->structureParser->setPositionManager(new PositionManager());
        $structure = $this->structureParser->parse(new StructureEntity($input, $input, $input));
        $result = array_map(function(Command $command) use ($lineParser) {
            return $lineParser->getLine($command->positionEntity->originalPosition);
        }, $structure->_commands);

        $this->assertEquals($expected, $result);
    }

    public static function data_lines()
    {
        return [

            ['
            ~ ', [0, 1]],

            ['
            ~    ~
            ', [0, 1, 1]],

            ['
            ~    ~


            ~     ~    ', [0, 1, 1, 4, 4]],

            ['
            ~    ~


            ~ sd    ~
            ~~~', [0, 1, 1, 4, 4, 5, 5, 5]],

        ];
    }

    public static function data_positions()
    {
        return [
            [' ~ ', [0, 2]],
            [' ~ hello~', [0, 2, 9]],
        ];
    }

    public static function data_parse()
    {
        return [
            ['', ['']],
            ['hello~world', ['hello', 'world']],
            [' command1

            ~

            command2  ~command3', ['command1', 'command2', 'command3']]
        ];
    }
}

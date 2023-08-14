<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Entity\SyntaxEntity;
use App\Backticks\Syntax\Exceptions\ParseErrorException;
use PHPUnit\Framework\TestCase;

class PreprocessorTest extends TestCase
{
    protected Preprocessor $preprocessor;
    public function __construct(string $name)
    {
        parent::__construct($name);

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
     * @dataProvider data_found_structures_count
     */
    public function test_found_structures_count($input, $expected)
    {
        $this->preprocessor->prepare($input);

        $this->assertEquals($expected, $this->preprocessor->getFoundStructuresCount());
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_positions
     */
    public function test_positions($input, $expected)
    {
        $this->preprocessor->prepare($input);

        $this->assertEquals($expected, array_map(function (SyntaxEntity $entity) {
            return $entity->positionEntity->originalPosition;
        }, $this->preprocessor->getAllEntities(true)));
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_lines
     */
    public function test_lines($input, $expected)
    {
        $this->preprocessor->prepare($input);

        $this->assertEquals($expected, array_map(function (SyntaxEntity $entity) {
            return $entity->positionEntity->line;
        }, $this->preprocessor->getAllEntities(true)));
    }

    public static function data_lines()
    {
        return [
            ["''", [0]],
            ["

            ''", [2]],
            ["
            `~
            ' hello '
            ~`
            ''", [1, 2, 4]],
            ["
            `~
            ' hel

            lo '
            ~`
            '' `~        'string'    do| or die ~`", [1, 2, 6, 6, 6]],
            ["
            `~
            ' hel

            lo '
            ~`
            '' `~        'string'  `~
              `~             ~`         '
                    `~ this one doesn\'t count since it\'s inside a string ~`
              ' `~     line9 ~`
              ~` do| or die ~`", [1, 2, 6, 6, 6, 6, 7, 7, 9]],
            ["
            `~
            ' hel

            lo '
            ~`
            '' `~        'string'  `~
              `~             ~`         '
                    `~ this one doesn\'t count since it\'s inside a string ~`
              ' `~     line9 ~`
              ~` do| or die ~`
              `~
              `~
                    `~

                    ~`
'#line16'

              ~`


              ~`", [1, 2, 6, 6, 6, 6, 7, 7, 9, 11, 12, 13, 16]],
        ];
    }

    public static function data_positions()
    {
        return [
            ["", []],
            ["''", [0]],
            ["''''", [0, 2]],
            ["''''`~~`", [0, 2, 4]],
            ["''''`~''~`'`~~`'", [0, 2, 4, 6, 10]],
            ["`~~``~~`", [0, 4]],
            ["`~~`''`~~`", [0, 4, 6]],
            ["''`~~`''`~~`", [0, 2, 6, 8]],
            ["''`~~`''''`~~`", [0, 2, 6, 8, 10]],
            ["''`~~`''''`~''~`", [0, 2, 6, 8, 10, 12]],
            ["''`~~`''''`~`~`~~`~`~``~~`", [0, 2, 6, 8, 10, 12, 14, 22]],
            ["''`~~`''''`~`~`~~`''~`~``~~`", [0, 2, 6, 8, 10, 12, 14, 18, 24]],

            /* @error error 'missing string delta' */
            ["''`~~`''''`~`~`~''~`''~`~``~~`", [0, 2, 6, 8, 10, 12, 14, 16, 20, 26]],
            ["''''`~''~`''`~~`", [0, 2, 4, 6, 10, 12]],
            ["''''`~''~`''`~`~~`~`", [0, 2, 4, 6, 10, 12, 14]],
            ["''''`~''~`''`~`~''''~`~`", [0, 2, 4, 6, 10, 12, 14, 16, 18]],
            ["''''`~''~`''`~`~''''`~~`~`~`", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20]],
            ["''''`~''~`''`~`~''''`~''~`~`~`", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20, 22]],
            ["''''`~''~`''`~`~''''`~''`~~`~`~`~`", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20, 22, 24]],
            ["''''`~''~`''`~`~''''`~''`~''~`~`~`~`", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20, 22, 24, 26]],
            ["''''`~''~`''`~`~''''`~''`~''`~~`~`~`~`~`", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28]],
            ["''''`~''~`''`~`~''''`~''`~''`~''~`~`~`~`~`", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30]],
            ["''''`~''~`''`~`~''''`~''`~''`~''`~~`~`~`~`~`~`", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32]],
            ["''''`~''~`''`~`~''''`~''`~''`~''`~~`'`~~`'~`~`~`~`~`", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 36]],
            ["''''`~''~`''`~`~''''`~''`~''`~''`~~`'`~~`'`~~`~`~`~`~`~`", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 36, 42]],
            ["''''`~''~`''`~`~''''`~''`~''`~''`~~`'`~~`'`~~`~`~`~`~`~`''", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 36, 42, 56]],
            ["''''`~''~`''`~`~''''`~''`~''`~''`~~`'`~~`'`~~`~`~`~`~`~`''`~~`", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 36, 42, 56, 58]],
            ["''''`~''~`''`~`~''''`~''`~''`~''`~~`'`~~`'`~~`~`~`~`~`~`''`~'last text'~`", [0, 2, 4, 6, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 36, 42, 56, 58, 60]],
            /* @enderror */
        ];
    }

    public static function data_found_structures_count() {
        return [
            ["", 0],
            ["
            ' `~       ~` '

            '\''

            ", 0],
            ["
            `~      `~   '
            '
              ~`   ~`

              ' '
              ' `~


              ~` '
            ", 2],
            ["
            `~      `~   '
            '
              ~`   ~`

              `~
              ' '
              ' `~


              ~` '
              ~`
            ", 3],
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_exceptions
     */
    public function test_exceptions($input, $expected)
    {
        $this->expectException($expected);
        $this->preprocessor->prepare($input);
    }

    public static function data_exceptions()
    {
        return [
            [" `~     '    ~` ", ParseErrorException::class],
            [" `~     '  '  ~`  `~  ", ParseErrorException::class],
            [" `~     '  '  ~`  `~
              '''''
              ~`", ParseErrorException::class],
        ];
    }

    /**
     * @param $input
     * @param null $expected
     * @dataProvider data_there_and_back_again
     */
    public function test_there_and_back_again($input, $expected = null)
    {
        $result = $this->preprocessor->prepare($input);
        $result = $this->preprocessor->replaceBack($result, true);

        $this->assertEquals($input, $result);
    }

    public static function data_there_and_back_again() {
        return [
            ['     '],
            ["  ''"],
            ["`~~`  '`~~`'"],
        ];
    }
}

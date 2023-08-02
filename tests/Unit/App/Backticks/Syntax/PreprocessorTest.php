<?php

namespace App\Backticks\Syntax;

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

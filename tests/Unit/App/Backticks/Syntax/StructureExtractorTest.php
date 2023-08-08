<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Entity\StructureEntity;
use App\Backticks\Syntax\Exceptions\ParseErrorException;
use App\Backticks\Syntax\DTO\StructureExtractorConfig;
use PHPUnit\Framework\TestCase;

class StructureExtractorTest extends TestCase
{
    protected StructureExtractor $structureExtractor;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->structureExtractor = new StructureExtractor();
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider data_match
     */
    public function test_match($input, $expected)
    {
        $result = $this->structureExtractor->matchStructures($input);

        $this->assertEquals($expected, $result[0]);
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider data_match2
     */
    public function test_match2($input, $expected)
    {
        $result = $this->structureExtractor->matchStructures($input);

        $this->assertEquals($expected, array_map(function($item){ return trim($item);}, $result[1]));
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider data_extract_levels
     */
    public function test_extract_levels($input, $expected)
    {
        $this->structureExtractor->setConfig(new StructureExtractorConfig(
            '',
            '',
            '{',
            '}',
        ));
        $result = $this->structureExtractor->extractStructures($input[0], $input[1]);
        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_extract_level_number
     */
    public function test_extract_level_number($input, $expected)
    {
        $this->structureExtractor->extractStructures($input);
//        $matches = $this->structureExtractor->matchStructures("    {10}");
//        print_r($matches);
        $this->assertEquals($expected, $this->structureExtractor->level);
//        $this->assertEquals(1, 1);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_all_the_way
     */
    public function test_all_the_way($input, $expected)
    {
        $this->structureExtractor->setConfig(new StructureExtractorConfig(
            '',
            '',
            '{',
            '}',
        ));
        $result = $this->structureExtractor->extractStructures($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_there_and_back_again
     */
    public function test_there_and_back_again($input, $expected)
    {
        $result = $this->structureExtractor->extractStructures($input);
        $back = $this->structureExtractor->replaceBack($result);
        $this->assertEquals($input, $back);

        $this->structureExtractor->clear();
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
        $result = $this->structureExtractor->extractStructures($input);
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_positions
     */
    public function test_positions($input, $expected)
    {
        $this->structureExtractor->extractStructures($input);
        $result = array_map(function(StructureEntity $entity){
            return $entity->originalPosition;
        }, $this->structureExtractor->getEntities(true));
        $this->assertEquals($expected, $result);
        $this->structureExtractor->clear();
    }

    /**
     * @param $input
     * @param $expected
     * @dataProvider data_lengths
     */
    public function test_lengths($input, $expected)
    {
        $this->structureExtractor->extractStructures($input);
        $result = array_map(function(StructureEntity $entity){
            return $entity->originalLength;
        }, $this->structureExtractor->getEntities(true));
        $this->assertEquals($expected, $result);
        $this->structureExtractor->clear();
    }

    public static function data_lengths() {
        return [
            ['`~~`', [4]],
            ['`~~``~~`', [4, 4]],
            ['`~~``~`~~`~`', [4, 8, 4]],
            ['`~`~~`~``~`~~`~`', [8, 4, 8, 4]],
            ['`~`~`~~`~`~``~`~~`~`', [12, 8, 4, 8, 4]],
            ['`~`~`~~`~`~``~`~~`~``~
~`', [12, 8, 4, 8, 4, 5]],
        ];
    }

    public static function data_positions()
    {
        return [
            ['`~~`', [0]],
            ['`~~``~~`', [0, 4]],
            [' `~        ~``~~`', [1, 13]],
            ['`~`~~`~``~`~~`~`', [0, 2, 8, 10]],
            ['`~`~~`~``~`~

    same thing here
            ~`
            noissse!
            ~`', [0, 2, 8, 10]],

            ['
            `~`~~`~``~`~~`~`', [13, 15, 21, 23]],
            ['`~`~~`~``~`~~`~`

`~          `~~`~`
            ', [0, 2, 8, 10, 18, 30]],

            ['`~`~`~~`~`~`', [0, 2, 4]],
            ['`~`~`~`~~`~`~`~``~`~`~`~~`~`~`~`', [0, 2, 4, 6, 16, 18, 20, 22]],
            ['`~`~`~`~   ~`~`~`~``~`~`~`~~`~`~`~`', [0, 2, 4, 6, 19, 21, 23, 25]],
            ['`~~``~`~~`~`', [0, 4, 6]],
            ['`~~``~`~`~~`~`~`', [0, 4, 6, 8]],
            ['`~`~`~~`~`~``~~`', [0, 2, 4, 12]],
            ['`~`~`~~`~`~``~~``~~`', [0, 2, 4, 12, 16]],
            ['`~`~`~~`~`~``~`~~`~``~~`', [0, 2, 4, 12, 14, 20]],
        ];
    }

    public static function data_exceptions() {
        return [
            ["`~
                ~`~`~`~`~`~`~`~`~`~  ~`~`~`~`~`~`~`~`~`
            ~`", ParseErrorException::class],
            ["`~
                `~`~`~`~`~`~~`~`~`~  ~`~`~`~`~`~`~`~`~`
            ~`", ParseErrorException::class],
        ];
    }

    public static function data_there_and_back_again()
    {
        $data = array_unique(array_merge(
            array_map(function($item){
                return $item[0];
            }, self::data_all_the_way()),

            array_map(function($item){
                return $item[0];
            }, self::data_extract_level_number()),

            array_map(function($item){
                return $item[0][0];
            }, self::data_extract_levels()),

            array_map(function($item){
                return $item[0];
            }, self::data_match()),

            array_map(function($item){
                return $item[0];
            }, self::data_match2()),
        ));

        return array_map(function ($item) {
            return [$item, null];
        }, $data);
    }

    public static function data_all_the_way()
    {
        return [
            [" hello `~ test ~` world", " hello {1} world"],
            ["Multilevel: `~`~`~`~`~`~`~`~`~`~  ~`~`~`~`~`~`~`~`~`~`", "Multilevel: {10}"],
            ["Multilevel with noise: `~hello`~`~noise`~<>`~! ~ `~`~!`~<`~.`~?  ~`~~~~~~~~` @~` #~` $ %~` '~` ~`
            ~`~`~`", "Multilevel with noise: {10}"],// you can add ~ after the closing ~` but not in front of the opening `~
            ["Multilevel with noise: `~hello`~`~noise`~<>`~! ~ `~`~!`~<`~.`~?  ~`~~~~~~~~` @~` #~` $ %~` '~` ~`
            ~`~`~` <div>`~
                `~ level2 ~`
            ~`", "Multilevel with noise: {12} <div>{4}"],
        ];
    }

    public static function data_extract_level_number()
    {
        return [
            ["`~~`", 1],
            ["`~`~~`~`", 2],
            ["`~`~~`~`     `~~`", 2],
            ["`~
                `~`~`~`~`~`~`~`~`~  ~`~`~`~`~`~`~`~`~`
            ~`", 10],
            ["`~
                `~`~`~`~`~`~`~`~`~  ~`~`~`~`~`~`~`~`~`
                                `~`~`~`~`~`~`~`~`~  ~`~`~`~`~`~`~`~`~`
                                                `~`~`~`~`~`~`~`~`~  ~`~`~`~`~`~`~`~`~`
            ~`", 10],
            ["`~
                `~`~`~`~`~`~`~`~`~
                    Let's add some noise

                 ~`~`~`~`~`~`~`~`~`
                                `~`~`~`~`~`~`~`~`~

And here some
                                 ~`~`~`~`~`~`~`~`~`
                                                `~`~`~`~`~`~`~`~`~
Very noisy {{{{}}}}

                                                 ~`~`~`~`~`~`~`~`~`
            ~`", 10],
            ["`~
                `~`~`~`~`~
                ~~~~~~~~~~~~~~~~~~~~~ ~~ ~ ~~ ~
                funny noi'se
                `~
                Here's some hel +++++++++++++++++++++++++++++''''''''''

                799  & & & & 0(8) ()* *
                `~
                Even noisier here {{{{{{}}}}}}} <<<<<<< >>>>>>> <. >< .,df . dsf $
                `~
                Very noisy level
                `~
                    Let's add some noise

                 ~`~`~`~`~`~`~`~`~`
                                `~`~`~`~`~`~`~`~`~

And here some
                                 ~`~`~`~`~`~`~`~`~`
                                                `~`~`~`~`~`~`~`~`~
Very noisy {{{{}}}}

                                                 ~` lal al la
                                                 this is awesome
                                                 ~`
more !<<> funny noise! !  ! ~~  ~
                                                 ~`~`~`~`~`~`~`
            ~`", 10],
        ];
    }

    public static function data_extract_levels()
    {
        return [
            [
                ["`~~`", 1],
                '{1}',
            ],
            [
                ["`~`~~`~`", 1],
                '`~{1}~`',
            ],
            [
                ["`~`~~``~ ~`~`", 1],
                '`~{1}{2}~`',
            ],
            [
                ["`~`~~``~~`~`", 1],
                '`~{1}{2}~`',
            ],
            [
                ["`~
                `~~``~~`
                ~`", 1],
                "`~
                {1}{2}
                ~`",
            ],
            [
                ["`~
                `~~``~~`~
                ~`", 1],
                "`~
                {1}{2}~
                ~`",
            ],
            [
                ["`~
                `~~`~`~~`~
                ~`", 1],
                "`~
                {1}~{2}~
                ~`",
            ],
            [
                ["`~
                `~~``~~`
                ~`
                `~ hello world ~`", 1],
                "`~
                {1}{2}
                ~`
                {3}",
            ],
            [
                ["`~
                `~~``~~`
                ~`
                `~ hello world ~`", 2],
                "{4}
                {3}",
            ],
            [
                ["    `~
            hello
                    `~ level2 ~`
                    `~ level2
                        `~ level3 ~`
                        `~ level3
                            `~ level4 ~`
                            `~ level4 again ~`
                            `~ lalalala
                                `~ level5 ~`
                            ~`
                        ~`
                    ~`
                    `~ more level2 ~`
            ~`", 1],
                "    `~
            hello
                    {1}
                    `~ level2
                        {2}
                        `~ level3
                            {3}
                            {4}
                            `~ lalalala
                                {5}
                            ~`
                        ~`
                    ~`
                    {6}
            ~`"],
            [
                ["    `~
            hello
                    `~ level2 ~`
                    `~ level2
                        `~ level3 ~`
                        `~ level3
                            `~ level4 ~`
                            `~ level4 again ~`
                            `~ lalalala
                                `~ level5 ~`
                            ~`
                        ~`
                    ~`
                    `~ more level2 ~`
            ~`", 2],
                "    `~
            hello
                    {1}
                    `~ level2
                        {2}
                        `~ level3
                            {3}
                            {4}
                            {7}
                        ~`
                    ~`
                    {6}
            ~`"],
            [
                ["    `~
            hello
                    `~ level2 ~`
                    `~ level2
                        `~ level3 ~`
                        `~ level3
                            `~ level4 ~`
                            `~ level4 again ~`
                            `~ lalalala
                                `~ level5 ~`
                            ~`
                        ~`
                    ~`
                    `~ more level2 ~`
            ~`", 3],
                "    `~
            hello
                    {1}
                    `~ level2
                        {2}
                        {8}
                    ~`
                    {6}
            ~`"],
            [
                ["    `~
            hello
                    `~ level2 ~`
                    `~ level2
                        `~ level3 ~`
                        `~ level3
                            `~ level4 ~`
                            `~ level4 again ~`
                            `~ lalalala
                                `~ level5 ~`
                            ~`
                        ~`
                    ~`
                    `~ more level2 ~`
            ~`", 4],
                "    `~
            hello
                    {1}
                    {9}
                    {6}
            ~`"],
            [
                ["    `~
            hello
                    `~ level2 ~`
                    `~ level2
                        `~ level3 ~`
                        `~ level3
                            `~ level4 ~`
                            `~ level4 again ~`
                            `~ lalalala
                                `~ level5 ~`
                            ~`
                        ~`
                    ~`
                    `~ more level2 ~`
            ~`", 5],
                "    {10}"],

            [["
                `~ here comes the code separaterd by ~
                    one more command ~
                    finally ~
                    `~
                        som more here ~
                        and here we go ~
                        `~
                            level three code ~
                            more here
                        ~` ~
                        finally ~

                        `~
                            another level3
                        ~` ~

                    ~`
                ~` <div here>
                `~ some funny ~
                    `~ hello ~`
                code here ~ ~`
            ", 1], "
                `~ here comes the code separaterd by ~
                    one more command ~
                    finally ~
                    `~
                        som more here ~
                        and here we go ~
                        {1} ~
                        finally ~

                        {2} ~

                    ~`
                ~` <div here>
                `~ some funny ~
                    {3}
                code here ~ ~`
            "],
            [["
                `~ here comes the code separaterd by ~
                    one more command ~
                    finally ~
                    `~
                        som more here ~
                        and here we go ~
                        `~
                            level three code ~
                            more here
                        ~` ~
                        finally ~

                        `~
                            another level3
                        ~` ~

                    ~`
                ~` <div here>
                `~ some funny ~
                    `~ hello ~`
                code here ~ ~`
            ", 2], "
                `~ here comes the code separaterd by ~
                    one more command ~
                    finally ~
                    {4}
                ~` <div here>
                {5}
            "],
            [["
                `~ here comes the code separaterd by ~
                    one more command ~
                    finally ~
                    `~
                        som more here ~
                        and here we go ~
                        `~
                            level three code ~
                            more here
                        ~` ~
                        finally ~

                        `~
                            another level3
                        ~` ~

                    ~`
                ~` <div here>
                `~ some funny ~
                    `~ hello ~`
                code here ~ ~`
            ", 3], "
                {6} <div here>
                {5}
            "],
        ];
    }

    public static function data_match(): array
    {
        return [
            [" `~ hello ~` world", ['`~ hello ~`']],
            [" `~~` `~ hello ~` world", ['`~~`', '`~ hello ~`']],
            [" `~  `~~`  ~` `~ hello ~` world", ['`~~`', '`~ hello ~`']],
            [" `~  `~second~` `~second2~`  ~` `~ hello ~` world", ['`~second~`', '`~second2~`', '`~ hello ~`']],
        ];
    }

    public static function data_match2(): array
    {
        return [
            [" `~ hello ~` world", ['hello']],
            ["`~    `~ level2 ~`      ~`  hello `~ level1 ~` ok", ['level2', 'level1']],
            ["`~    `~ level2 ~`  `~ level2         `~level3~`   ~`     ~`  hello `~ level1 ~` ok", ['level2', 'level3', 'level1']],
            ["    `~
            hello
            ~`", ['hello']],
            ["    `~
            hello
                    `~ level2 ~`
                    `~ level2
                        `~ level3 ~`
                        `~ level3
                              `~ level4 ~`
                        ~`
                    ~`
            ~`", ['level2', 'level3', 'level4']],
            ["    `~
            hello
                    `~ level2 ~`
                    `~ level2
                        `~ level3 ~`
                        `~ level3
                            `~ level4 ~`
                            `~ level4 again ~`
                            `~ lalalala
                                `~ level5 ~`
                            ~`
                        ~`
                    ~`
                    `~ more level2 ~`
            ~`", ['level2', 'level3', 'level4', 'level4 again', 'level5', 'more level2']],
        ];
    }
}

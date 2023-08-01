<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Preprocessor\DTO\Config;
use PHPUnit\Framework\TestCase;

class PreprocessorTest extends TestCase
{
    protected Preprocessor $preprocessor;
    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->preprocessor = new Preprocessor();
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider data_match
     */
    public function test_match($input, $expected)
    {
        $result = $this->preprocessor->matchStructures($input);

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
        $result = $this->preprocessor->matchStructures($input);

        $this->assertEquals($expected, array_map(function($item){ return trim($item);}, $result[1]));
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider data_extract_level1
     */
    public function test_extract_level1($input, $expected)
    {
        $this->preprocessor->setConfig(new Config(
            '',
            '',
            '{',
            '}',
        ));
        $result = $this->preprocessor->extractStructures($input, 1);
        $this->assertEquals($expected, $result);
    }

    public static function data_extract_level1()
    {
        return [
            [
                "`~~`",
                '{1}',
            ],
            [
                "`~`~~`~`",
                '`~{1}~`',
            ],
            [
                "`~`~~``~ ~`~`",
                '`~{1}{2}~`',
            ],
            [
                "`~`~~``~~`~`",
                '`~{1}{1}~`',
            ],
            [
                "`~
                `~~``~~`
                ~`",
                "`~
                {1}{1}
                ~`",
            ],
            [
                "`~
                `~~``~~`
                ~`
                `~ hello world ~`",
                "`~
                {1}{1}
                ~`
                {2}",
            ],
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
            ~`",
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
            ~`"]
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

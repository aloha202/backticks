<?php

namespace App\Libraries\QueryParser\Preparator;

use Tests\TestCase;

class StringExtractorTest extends TestCase
{
    protected const STRING_1 = "hello 'world'";
    protected const STRING_2 = "hello '>>>>' again, 'world etc', ";
    protected const STRING_3 = "hello '' again '\''";
    protected const STRING_4 = "Welcome, 'quoted with \'escaped\''";
    protected const STRING_5 = "Hello, '<notatag>' and '<notag>>> with \' quotes \' and' stuff, yeah ''";
    protected $stringExtractor;
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->stringExtractor = new StringExtractor();
    }

    public function testThereAndBackAgain() {

        foreach([
            self::STRING_1, self::STRING_2, self::STRING_3, self::STRING_4, self::STRING_5
        ] as $query) {
            $replaced = $this->stringExtractor->prepareStringLiterals($query);
            $result = $this->stringExtractor->replaceBack($replaced, true);
            $this->assertEquals($query, $result);
        }

    }

    /**
     * @return void
     * @dataProvider dataExtractValues
     */
    public function testExtractValues($input, $expected)
    {
        $result = $this->stringExtractor->prepareStringLiterals($input);
        $backResult = $this->stringExtractor->replaceBack($result);
        $this->assertEquals($expected, $backResult);
    }

    public function testPrepareStringLiterals() {

        foreach($this->dataPrepareStringLiterals() as $dataItem) {
            $result = $this->stringExtractor->prepareStringLiterals($dataItem[0]);
            $literalNames = [];
            for($i = 0; $i < $dataItem[2]; $i++) {
                $literalNames[] = $this->stringExtractor->makeName($i);
            }
            $this->assertEquals(sprintf($dataItem[1], ...$literalNames), $result);
        }

    }


    public function testExtractStringLiterals()
    {
        foreach($this->dataExtractStringLiterals() as $dataItem) {
            $result = $this->stringExtractor->extractStringLiterals($dataItem[0]);
            $this->assertEquals($dataItem[1], $result);
        }
    }

    public static function dataExtractValues() {
        return [
            ["hello '' again '\''", "hello  again '"],
            ["More 'quoted' strings with 'funny\'' 'val\"ues'", "More quoted strings with funny' val\"ues"],
            ["''''''", ""],
            ["'\'''\'''\'''\''", "''''"],
        ];
    }


    public static function dataPrepareStringLiterals() {

        return [
            [self::STRING_1, "hello %s", 1],
            [self::STRING_2, "hello %s again, %s, ", 2],
            [self::STRING_3, "hello %s again %s", 2],
            [self::STRING_4, "Welcome, %s", 1],
            [self::STRING_5, "Hello, %s and %s stuff, yeah %s", 3],
        ];

    }

    public static function dataExtractStringLiterals()
    {
        return [
            [self::STRING_1, ["'world'"]],
            [self::STRING_2, ["'>>>>'", "'world etc'"]],
            [self::STRING_3, ["''", "'\''"]],
            [self::STRING_4, ["'quoted with \'escaped\''"]],
            [self::STRING_5, ["'<notatag>'", "'<notag>>> with \' quotes \' and'", "''"]],
        ];
    }
}

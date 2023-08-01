<?php

namespace App\Libraries\QueryParser\Preparator;

use App\Libraries\QueryParser\Evaluator\Tag\Method;
use App\Libraries\QueryParser\Exception\InvalidOperatorException;
use Tests\TestCase;

class OperatorExtractorTest extends TestCase
{
    protected $operatorExtractor;
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->operatorExtractor = new OperatorExtractor();
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataException
     */
    public function testException($input, $expected) {
        $this->expectException($expected[0]);
        $this->expectExceptionMessage($expected[1]);
        $this->operatorExtractor->prepareOperators($input);
    }

    /**
     * @param $input
     * @return void
     * @dataProvider dataThereAndBackAgain
     */
    public function testThereAndBackAgain($input) {
        $result = $this->operatorExtractor->prepareOperators($input);
        $result = $this->operatorExtractor->replaceBack($result, true);

        $this->assertEquals($input, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataReplaceWithValues
     */
    public function testReplaceWithValues($input, $expected) {
        $result = $this->operatorExtractor->prepareOperators($input);
        $this->assertEquals($expected, $this->operatorExtractor->replaceBack($result));
    }

    /**
     * @return void
     * @dataProvider dataWithMethods
     */
    public function testWithMethods($input, $expected) {
        $prepared = $this->operatorExtractor->prepareOperators($input);
        $method = new Method($prepared, $this->operatorExtractor);
        $this->assertEquals($expected[0], $method->methodName);
        $this->assertEquals($expected[1], $method->parameters);
        $this->operatorExtractor->clear();
    }

    public static function dataWithMethods() {
        return [
            ["`if`:`>`:1:`then`:2", ['if', ['gt', 1, 'then', 2]]],
            ["`IF` : `!=` : 1 : `ELSE` : `elsEif`:heRE", ['if', ['ne', 1, 'else', 'elseif', 'heRE']]],
        ];
    }

    public static function dataReplaceWithValues(): array
    {
        return [
            ["`<`", 'lt'],
            [" 1 `<>` 3", ' 1 ne2 3'],
            [
                " if 1 `<>` 3 `AnD` bl `thEn` go home `elseif` b `>` c `oR` b `<` 2 `then` OK",
                " if 1 ne2 3 and bl then go home elseif b gt c or b lt 2 then OK",
            ]
        ];

    }

    public static function dataThereAndBackAgain() {
        return [
            ["    `and`  "],
            ["`And` `or` `ELSE` `elseIF`   <><> `<>` `!=` `<``>`"],
        ];
    }

    public static function dataException() {
        return [
            [" hello `===` wordl", [InvalidOperatorException::class, "`===`"]],
            [" hello `<``world`", [InvalidOperatorException::class, "`world`"]],
            [" valid: `<>`    invalid: ` =`", [InvalidOperatorException::class, "` =`"]],
        ];
    }
}

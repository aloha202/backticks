<?php

namespace App\Libraries\QueryParser\Evaluator;

use App\Libraries\QueryParser;
use App\Libraries\QueryParser\Conditional;
use App\Libraries\QueryParser\Evaluator\Conditional\Exception\MalformedConditionalException;
use App\Libraries\QueryParser\Evaluator\Conditional\Exception\MissingOperatorException;
use App\Libraries\QueryParser\Evaluator\Conditional\Exception\UnexpectedCharacterException;
use App\Libraries\QueryParser\Evaluator\Conditional\Left;
use App\Libraries\QueryParser\Evaluator\Conditional\Right;
use App\Libraries\QueryParser\Preparator\Operator;
use App\Libraries\QueryParser\Processor\ConditionalProcessor;
use Tests\TestCase;
class ConditionalEvaluatorTest extends TestCase
{
    use QueryParser\Mock\MockRepoTrait;
    use QueryParser\Mock\MockDataTrait;
    /**
     * @var ConditionalEvaluator $conditionalEvaluator
     */
    protected $conditionalEvaluator;
    /**
     * @var QueryParser $queryParser;
     */
    protected $queryParser;
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->queryParser = new QueryParser($this, $this);
        $this->conditionalEvaluator = new ConditionalEvaluator($this->queryParser, new ConditionalProcessor());
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataParts
     */
    public function testParts($input, $expected) {
        $result =$this->conditionalEvaluator->extractParts($input);
        $this->assertEquals($expected, array_map(
            function ($item) {
                return $item->value;
            },
            $result));
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataPartsClass
     *
     */
    public function testPartsClass($input, $expected)
    {
        $result =$this->conditionalEvaluator->extractParts($input);
        $this->assertEquals($expected, array_map(
            function ($item) {
                return get_class($item);
            },
            $result));
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataExceptions
     */
    public function testExceptions($input, $expected)
    {
        $this->expectException($expected[0]);
        if (isset($expected[1])) {
            $this->expectExceptionMessage($expected[1]);
        }
        $this->conditionalEvaluator->extractParts($input);
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataSplitLeft
     */
    public function testSplitLeft($input, $expected)
    {
        $result = $this->conditionalEvaluator->splitLeft(new Left($input));
        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataSplitLeftAndEvaluateTags
     */
    public function testSplitLeftAndEvaluateTags($input, $expected)
    {
        $replaced = $this->queryParser->_prepareStrings($input);
        $result = $this->conditionalEvaluator->evaluateLeft(new Left($replaced));

        $this->assertEquals($expected, $result);

    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataPrepareForProcessing
     */
    public function testPrepareForProcessing($input, $expected)
    {
        $prepared = $this->queryParser->_prepareStrings($input);
        $parts = $this->conditionalEvaluator->extractParts($prepared);
        $parts = $this->conditionalEvaluator->evaluateParts($parts);
        $params = $this->conditionalEvaluator->prepareConditional($parts);

        $params = array_map(function ($item) {
            return $item instanceof Operator ? $item->value : $item;
        }, $params);

        $this->assertEquals($expected, $params);
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataEval
     */
    public function testEval($input, $expected)
    {
        $prepared = $this->queryParser->_prepareStrings($input);
        $result = $this->conditionalEvaluator->evaluate(new Conditional($prepared, $prepared, 0));

        $this->assertEquals($expected, $result);
    }

    public static function dataEval()
    {
        return [
            ["(a `==` b){yes}else {no}", 'no'],
            ["(1 `<` <number10> ){'Number 10!'} else {''}", 'Number 10!'],
            ["(<tag>){'OK'}", 'OK'],
            ["   (<any | arg1 : 10 : plus : 20> `>` <year20>)
    {'Greater'}
     ELSE
     {'Smaller'}   ", 'Smaller'],
            ["    (<com1
                        ::@year| join : ''>
                        `>`
                        <year21>
                        )
                        {
                        'Greater than': <year21>
                        }
                        ELSE
                        {
                        'Smaller than': <year21>
                        }", 'Greater than: 2017'],
            ["
            (<object ::@name|upper|concat:'*'> `==` 'MIKE*'  `or` <object ::@name|upper|concat:'*'> `==` 'JOHN*')
            {'It\'s John or Mike!'}
            ELSE
            {'None of them'}
            ", "It's John or Mike!"],
            ["(1 `OR` 0) {Yes} ELSE {No}", 'Yes'],
            ["(<tag> `AND` <number10 | plus: 10> `>` 50)
                {<any|arg1: and worked |upper>}
                ELSEIF (<tag> `OR` <number10 | plus: 10> `>` 50)
                {<any|arg1: or worked |upper>}
                ELSE
                {NONE WORKED}
                ", "OR WORKED"],
        ];
    }

    public static function dataPrepareForProcessing()
    {
        return [
            ["(a `<` b) {return}", ['a', 'lt', 'b', 'then', 'return']],
            [
                "(<tag> `==` <any | upper>) {<any>} else {<tag>}",
                ['tag', 'eq2', 'ANY', 'then', 'any', 'else', 'tag'],
            ],
            [
                "(<tag> `==` <any | upper> `or` <tag> `==` 1) {<any>} else {<tag>}",
                ['tag', 'eq2', 'ANY', 'or', 'tag', 'eq2', 1, 'then', 'any', 'else', 'tag'],
            ],
            [
                "(<com1 | index : 0 ::@name|upper> `<=` 'Johnny') {'Johnny'} elseif (<year20 | plus : 10> `>` 1900) {<year20>} else {'Bad'}",
                ['JOHN', 'lte', 'Johnny', 'then', 'Johnny', 'elseif', 1975, 'gt', 1900, 'then', 1965, 'else', 'Bad'],
            ]
        ];
    }

    public static function dataSplitLeftAndEvaluateTags()
    {
        return [
            ["(<number10> `<` 10)", [10, "`<`", 10]],
            ["(<number10 | plus : 10> `==` '50')", [20, "`==`", 50]],
            [
                "( <com1 | index: 0 ::@name| upper> `and` 'Eleven thousand' <tag> `or` Hello world)",
                ['JOHN', '`and`', "Eleven thousand tag", '`or`', 'Hello world'],
            ]
        ];
    }

    public static function dataSplitLeft() {
        return [
            ["(a `<` b)", ['a', '`<`', 'b']],
            ["(a `==` b `and` b `!=` a)", ['a', '`==`', 'b', '`and`', 'b', '`!=`', 'a']],
            [
                "(a `or` b + 1 `and` C# `<=` hello world)",
                ['a', '`or`', 'b + 1', '`and`', 'C#', '`<=`', 'hello world']
            ],
        ];
    }

    public static function dataExceptions()
    {
        return [
            ['dsfsad fsdfsa', [UnexpectedCharacterException::class, "'d'"]],
            ["       ", [MalformedConditionalException::class, "missing '('"]],
            ["     ( x < z  ", [MalformedConditionalException::class, "missing ')'"]],
            ["     ( x < z  ) {do this} elseif ( then", [MalformedConditionalException::class, "missing ')'"]],
            [" ( hello )", [MalformedConditionalException::class, 'missing right part']],
            [" ( hello ) { then la la la", [MalformedConditionalException::class, "missing '}'"]],
            [" ( hello ) { then la la la }{ go go }", [MissingOperatorException::class, "'else'"]],
            ["( hello ){ then la la la }eles{ go go }", [MissingOperatorException::class, "'else'"]],
            ["( hello ) {} else ", [MalformedConditionalException::class, "Missing '{'"]],
            ["( hello ) {} elseif ", [MalformedConditionalException::class, "Missing '('"]],
            ["( hello ) {} elseifa ", [UnexpectedCharacterException::class, "'e'"]],
            ["( hello ) {} else{} hello ", [UnexpectedCharacterException::class, "'h'"]],
            ["( hello ) {} elseif(){} hello ", [UnexpectedCharacterException::class, "'h'"]],
            ["( hello ) {} elseif(){} else { } else", [UnexpectedCharacterException::class, "'e'"]],
            ["( hello ) {} elseif(){} else { }(a < b)", [UnexpectedCharacterException::class, "'('"]],
            ["( hello ) {} elseif(){} else { } { more }", [UnexpectedCharacterException::class, "'{'"]],
        ];
    }

    public static function dataParts()
    {
        return [
            ["(){}", ['()', '{}']],
            ["(a < b) {return}", ['(a < b)', '{return}']],
            ["(a < b) {yes} else {no}", ['(a < b)', '{yes}', '{no}']],
            ["\n(c < a && d > b) \t {yes} elseif \r  (no) {no} else {else}", ['(c < a && d > b)', '{yes}', '(no)', '{no}', '{else}']],
            ["( hello ) {} elseif(){} ", ['( hello )', '{}', '()', '{}']],
        ];
    }

    public static function dataPartsClass(): array
    {
        return [
            ["(){}", [Left::class, Right::class]],
            ["( i < 0 and z > 0) {empty} else {false}", [Left::class, Right::class, Right::class]],
        ];
    }
}

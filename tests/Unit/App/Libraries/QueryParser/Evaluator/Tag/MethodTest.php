<?php

namespace App\Libraries\QueryParser\Evaluator\Tag;

use App\Components\MailComponent;
use App\Libraries\QueryParser\Exception\InvalidMethodNameException;
use App\Libraries\QueryParser\Exception\InvalidMethodParameterException;
use App\Libraries\QueryParser\Preparator\Operator;
use App\Libraries\QueryParser\Preparator\OperatorExtractor;
use Tests\TestCase;
class MethodTest extends TestCase
{
    /**
     * @param $input
     * @return void
     * @dataProvider dataBadMethods
     */
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
     * @dataProvider dataBadMethods
     */
    public function testInvalidMethodName($input, $expected)
    {
        $this->expectException($expected);
        new Method($input);
    }
    public function testMethodEvaluation()
    {
        foreach ($this->dataMethodEvaluation() as $dataItem) {
            $method = new Method($dataItem[0]);
            $this->assertEquals($dataItem[1], $method->methodName);
            $this->assertEquals($dataItem[2], $method->parameters);
        }
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataOperators
     */
    public function testOperators($input, $expected)
    {
        $method = new Method($this->operatorExtractor->prepareOperators($input), $this->operatorExtractor);
        foreach($expected as $key) {
            $this->assertInstanceOf(Operator::class, $method->parameters[$key]);
        }
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataOperatorsToString
     */
    public function testOperatorsToString($input, $expected) {
        $method = new Method($this->operatorExtractor->prepareOperators($input), $this->operatorExtractor);
        $this->assertEquals($expected, join('', $method->all));
    }

    public static function dataOperatorsToString() {
        return [
            ["`if`:`=`:1:`then`:2:`Else`:12", "ifeq1then2else12"],
            ["IF:this:`<=`:  ' that ' : `THEN`: 12   : `and`: `or`: `ELSeif`", "ifthislte' that 'then12andorelseif"],
        ];
    }

    public static function dataOperators()
    {
        return [
            ["if:`>`:1", [0]],
            ["if : 222 : 'hello' : `then` : `else` : 111", [2,3]],
        ];
    }

    public static function dataMethodEvaluation()
    {
        return [
            ['index:3', 'index', [3]],
            ['toUpperCase', 'toUpperCase', []],
            ["regexSearchReplace:'/PO/':'Billing_ID_'", 'regexSearchReplace', ["'/PO/'", "'Billing_ID_'"]],
            ["regexCaptureGroup:/\d{2}$/", 'regexCaptureGroup', ['/\d{2}$/']],
            ['method2:-100', 'method2', [-100]],
            ['method3:, world', 'method3', [', world']],
            ['method3: Wells ', 'method3', ['Wells']],
            ['method3:          Wells        ', 'method3', ['Wells']],
            [" method3 : \n Wells", 'method3', ['Wells']],
            ["  \r method3\t : \n Connan Doyle ", 'method3', ['Connan Doyle']],
        ];
    }

    public static function dataBadMethods()
    {
        return [
            ['', InvalidMethodNameException::class],
            ['  ', InvalidMethodNameException::class],
            ["bad\tmethod", InvalidMethodNameException::class],
            ['hello world', InvalidMethodNameException::class],
            ['@invalid', InvalidMethodNameException::class],
            [',method', InvalidMethodNameException::class],
            ['bad-method', InvalidMethodNameException::class],
            ['.method', InvalidMethodNameException::class],
            ['bad<method', InvalidMethodNameException::class],
            ['very*bad', InvalidMethodNameException::class],
            ['$method', InvalidMethodNameException::class],
            ['method:', InvalidMethodParameterException::class],
            ['method::', InvalidMethodParameterException::class],
            ['method:1:', InvalidMethodParameterException::class],
            ['method : : :', InvalidMethodParameterException::class],
        ];
    }

}

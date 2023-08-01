<?php

namespace App\Libraries\QueryParser\Data;

use App\Libraries\QueryParser\Evaluator\Tag\Method;
use App\Libraries\QueryParser\Exception\MethodRepositoryException;
use App\Libraries\QueryParser\Exception\NonSystemMethodWithOperatorsException;
use App\Libraries\QueryParser\Preparator\OperatorExtractor;
use Tests\TestCase;

class MethodEvaluatorTest extends TestCase
{
    protected $methodEvaluator;
    /**
     * @var OperatorExtractor $operatorExtractor
     */
    protected $operatorExtractor;
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->methodEvaluator = new MethodEvaluator($this);
        $this->operatorExtractor = new OperatorExtractor();
    }

    /**
     * @return void
     * @dataProvider dataExceptions
     */
    public function testExceptions($input, $expected) {
        $this->expectException($expected);
        $method = new Method($this->operatorExtractor->prepareOperators($input), $this->operatorExtractor);
        $this->methodEvaluator->evaluate($method, 'context');
    }

    public static function dataExceptions()
    {
        return [
            ['any', MethodRepositoryException::class],
            ["hello:`else`", NonSystemMethodWithOperatorsException::class],
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @throws MethodRepositoryException
     * @dataProvider dataSystemMethods
     */
    public function testSystemMethods($input, $expected)
    {
        $this->methodEvaluator->setSystemMethodRepository($this);
        $method = new Method($input);
        $result = $this->methodEvaluator->evaluate($method, '');
        $this->assertEquals($expected, $result);
    }

    public static function dataSystemMethods()
    {
        return [
            ['if:1:then:2', 'if1then2'],
            ['IF:2:then:3', 'if2then3'],
        ];
    }

    /** Mock system repository function */
    public function callSystemMethod(string $methodName, array $methodParams)
    {
        return $methodName . join('', $methodParams);
    }

}

<?php

namespace App\Libraries\QueryParser\Processor;

use App\Libraries\QueryParser\Preparator\Operator;
use App\Libraries\QueryParser\Processor\Exception\BadOperatorException;
use App\Libraries\QueryParser\Processor\Exception\ConditionalProcessorException;
use App\Libraries\QueryParser\Processor\Exception\DoubleOperatorException;
use App\Libraries\QueryParser\Processor\Exception\MissingLeftPartException;
use App\Libraries\QueryParser\Processor\Exception\NotEnoughParametersException;
use App\Libraries\QueryParser\Processor\Exception\ThenIsMissingException;
use Respect\Validation\Rules\No;
use Tests\TestCase;

class ConditionalProcessorTest extends TestCase
{
    /**
     * @var ConditionalProcessor $conditionalProcessor
     */
    protected $conditionalProcessor;
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->conditionalProcessor = new ConditionalProcessor();
    }


    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataEval
     */
    public function testEval($input, $expected)
    {
        $result = $this->conditionalProcessor->process($input);
        $this->assertEquals($expected, $result);
    }

    public static function dataEval() {
        return [
            [[1, self::_then(), 'hello'], 'hello'],
            [[false, self::_then(), 'hello', self::_else(), 'goodbye'], 'goodbye'],
            [[0, self::_then(), 'zero', self::_elseif(), false, self::_then(), 'false', self::_else(), 'none'], 'none'],
            [[
                5, self::_ne(), 10, self::_and(), "john", self::_eq(), 'mike', self::_then(), 'johnmike',
                self::_elseif(),
                false, self::_then(), 'false',
                self::_elseif(),
                true, self::_and(), 5, self::_gte(), 4, self::_then(), 'Works',
                self::_else(), 'no'
            ], 'Works'],
            [
                [
                    'Works', self::_or(), 'Equals', self::_and(), 0, self::_then(), 'Equals',
                    self::_elseif(),
                    2000, self::_lte(), 100, self::_and(), '100', self::_ne(), '100', self::_then(), 'Not works',
                    self::_else(), 'Works!!'
                ],
                'Works!!'
            ]
        ];
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataEvalItem
     */
    public function testEvalItem($input, $expected)
    {
        $result = $this->conditionalProcessor->evaluateItem(...$input);
        $this->assertEquals($expected, $result);
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataEvalLeft
     */
    public function testEvalLeft($input, $expected) {
        $result = $this->conditionalProcessor->evaluateLeft($input);
        $this->assertEquals($expected, $result);
    }

    public static function dataEvalLeft()
    {
        return [
            [[1], true],
            [[0], false],
            [[''], false],
            [[1, self::_gt(), -1], true],
            [['hello', self::_and(), 'world'], true],
            [[false, self::_or(), true], true],
            [[true, self::_and(), 1, self::_gte(), 0], true],
            [[100, self::_gte(), 0, self::_and(), false], false],
            [[1, self::_and(), 2, self::_and(), 3], true],
            [[1, self::_and(), 2, self::_and(), 3, self::_or(), 5, self::_eq(), 10], true],
            [['100', self::_eq(), 0, self::_or(), 500, self::_gte(), 1000, self::_or(), false, self::_eq(), true], false],
            [['100', self::_and(), 500, self::_ne(), 1000, self::_and(), 0], false],
        ];
    }

    public static function dataEvalItem() {
        return [
            [[1], true],
            [[false], false],
            [[''], false],
            [[1, self::_eq(), 2], false],
            [[5, self::_op('gt'), 3], true],
            [['hello', self::_op('eq'), 'hello'], true],
            [['Mary', self::_op('eq'), 'sea'], false],
            [['toast', self::_op('ne'), 2], true],
            [['hello'], true],
            [[true, self::_op('and'), false], false],
            [[true, self::_op('or'), false], true],
        ];
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
        if (!empty($expected[1])) {
            $this->expectExceptionMessage($expected[1]);
        }

        $this->conditionalProcessor->process($input);
    }

    public static function dataExceptions(): array
    {
        return [
            [[], [NotEnoughParametersException::class]],
            [[true], [ThenIsMissingException::class]],
            [[1, self::_eq(), false], [ThenIsMissingException::class]],
            [["1", self::_else(), 'hello'], [ThenIsMissingException::class]],
            [[self::_then(), "hello"], [ConditionalProcessorException::class, 'first parameter can not be operator']],
            [[self::_then(), self::_else()], [ConditionalProcessorException::class, 'first parameter can not be operator']],
            [[1, self::_eq(), 1, self::_then(), self::_eq(), 1], [DoubleOperatorException::class, "'then' 'eq'"]],
            [[1, self::_then(), self::_then(), 2], [DoubleOperatorException::class, "'then' 'then'"]],
            [['hello', self::_lte(), 2, self::_then()], [BadOperatorException::class, "Unresolved operator: 'then'"]],
            [['hello', self::_lte(), 2, self::_then(), 17, self::_else()], [BadOperatorException::class, "Unresolved operator: 'else'"]],
            [['hello', self::_lte(), 2, self::_then(), 17, self::_elseif(), 12, self::_lte()], [BadOperatorException::class, "Unresolved operator: 'lte'"]],
        ];
    }

    protected static function _op($val) {
        return new Operator($val, $val, $val);
    }

    protected static function _eq()
    {
        return self::_op('eq');
    }

    protected static function _ne()
    {
        return self::_op('ne');
    }

    protected static function _lt()
    {
        return self::_op('lt');
    }

    protected static function _gt()
    {
        return self::_op('gt');
    }

    protected static function _lte()
    {
        return self::_op('lte');
    }

    protected static function _gte()
    {
        return self::_op('gte');
    }

    protected static function _and()
    {
        return self::_op('and');
    }

    protected static function _or()
    {
        return self::_op('or');
    }

    protected static function _then()
    {
        return self::_op('then');
    }

    protected static function _else() {
        return self::_op('else');
    }

    protected static function _elseif() {
        return self::_op('elseif');
    }
}

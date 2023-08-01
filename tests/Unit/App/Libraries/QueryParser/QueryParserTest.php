<?php

namespace App\Libraries\QueryParser;

use App\Libraries\QueryParser;
use App\Libraries\QueryParser\Exception\ExecutionErrorException;
use App\Libraries\QueryParser\Exception\SyntaxErrorException;
use App\Libraries\QueryParser\Mock\MockDataTrait;
use App\Libraries\QueryParser\Mock\MockRepoTrait;
use App\Libraries\QueryParser\Processor\Exception\ConditionalProcessorException;
use Tests\TestCase;

class QueryParserTest extends TestCase
{
    use MockRepoTrait;
    use MockDataTrait;
    protected $queryParser;
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->queryParser = new QueryParser($this, $this);
    }

    /**
     * @return void
     * @dataProvider dataExceptions
     */
    public function testExceptions($input, $expected)
    {
        if (is_array($expected)) {
            $this->expectException($expected[0]);
            $this->expectExceptionMessage($expected[1]);
        } else {
            $this->expectException($expected);
        }
        $this->queryParser->parse($input);
    }

    /**
     * @param $input
     * @param $expected
     * @return void
     * @dataProvider dataConditionalsException
     */
    public function testConditionalsException($input, $expected)
    {
        $this->expectException($expected);
        $this->queryParser->parse($input);
    }

    /**
     * @return void
     * @dataProvider dataParse
     */
    public function testParse($input, $expected)
    {
        $result = $this->queryParser->parse($input);
        $this->assertEquals($expected, $result);
    }

    public static function dataParse(): array
    {
        return self::mockExamples();
    }

    public static function dataExceptions():array
    {
        return self::mockExceptions();
    }


    public static function dataConditionalsException() {
        return self::mockConditionalExceptions();
    }

}

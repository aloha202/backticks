<?php

namespace App\Libraries\QueryParser\Data;

use App\Libraries\QueryParser\Evaluator\Tag\PropertyExpression;
use App\Libraries\QueryParser\Exception\NestedCollectionException;
use App\Libraries\QueryParser\Exception\NotAnObjectException;
use App\Libraries\QueryParser\Exception\UndefinedPropertyException;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class PropertyEvaluatorTest extends TestCase
{
    protected $propertyEvaluator;
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->propertyEvaluator = new PropertyEvaluator();
    }

    /**
     * @param $input
     * @return void
     * @dataProvider dataNotAnObjectException
     */
    public function testNotAnObjectException($input)
    {
        $this->expectException(NotAnObjectException::class);
        $pe = new PropertyExpression($input[0]);
        $this->propertyEvaluator->evaluate($pe, $input[1]);
    }

    /**
     * @return void
     * @dataProvider dataUndefinedPropertyException
     */
    public function testUndefinedPropertyException($input)
    {
        $this->expectException(UndefinedPropertyException::class);
        $pe = new PropertyExpression($input[0]);
        $this->propertyEvaluator->evaluate($pe, $input[1]);
    }

    public function testNestedCollectionException() {
        $this->expectException(NestedCollectionException::class);
        $pe = new PropertyExpression('@name');
        $this->propertyEvaluator->evaluate($pe, new Collection([self::dataObject1(), new Collection([1])]));
    }

    public static function dataUndefinedPropertyException(): array
    {
        return [
            [['@name', new \stdClass()], null],
            [['@name @value', self::dataObject1()], null],
        ];
    }

    public static function dataNotAnObjectException(): array
    {
        return [
            [['@name', 'string_context'], null],
            [['@name @value', ['array', 'of', 'strings']], null],
            [['@name', new Collection(['hello'])], null],
        ];
    }

    public function testEvaluate() {

        foreach($this->dataEvaluate() as $dataItem) {
            $pe = new PropertyExpression($dataItem[0]);

            $result = $this->propertyEvaluator->evaluate($pe, $dataItem[1]);

            $this->assertEquals($dataItem[2], $result);
        }
    }

    protected static function dataEvaluate() {
        return [
            ['@name', self::dataObject1(), 'John'],
            ['Hello, @name', self::dataObject1(), 'Hello, John'],
            ['Hey @first_name @last_name', self::dataObject2(), 'Hey John Doe'],
            ['@name', self::dataObject3(), ['John', 'Mike', 'Bunny']],
            ['Hello @name, @age', self::dataObject3(), ['Hello John, 21', 'Hello Mike, 22', 'Hello Bunny, 23']],
        ];
    }

    public static function dataObject1()
    {
        $object = new \stdClass();
        $object->name = 'John';
        return $object;
    }

    public static function dataObject2() {
        $object = new \stdClass();
        $object->first_name = 'John';
        $object->last_name = 'Doe';
        return $object;
    }

    public static function dataObject3() {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj3 = new \stdClass();

        $obj1->name = 'John';
        $obj1->age = 21;
        $obj2->name = 'Mike';
        $obj2->age = 22;
        $obj3->name = 'Bunny';
        $obj3->age = 23;

        return [$obj1, $obj2, $obj3];
    }
}

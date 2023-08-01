<?php

namespace App\Libraries\QueryParser\Evaluator\Tag;

use Tests\TestCase;
class PropertyExpressionTest extends TestCase
{

    /**
     * @return void
     * @dataProvider dataEvaluation
     */
    public function testPropertyExpressionEvaluation($input, $expected)
    {

        $propertyExpression = new PropertyExpression($input);
        $this->assertEquals($expected, $propertyExpression->properties);

    }
    public static function dataEvaluation()
    {
        return [
            ['@first_name', ['first_name']],
            ['@first_name @last_name', ['first_name', 'last_name']],
            ['@first-name, @hello-world', ['first', 'hello']],
            ['@position @ @value, hello', ['position', 'value']],
            ["I'm a @position at @company", ['position', 'company']],
            ["@crammed@up", ['crammed', 'up']],
        ];
    }
}

<?php

namespace App\Libraries\QueryParser\Evaluator;

use App\Libraries\QueryParser;
use App\Libraries\QueryParser\Evaluator\Tag\Method;
use App\Libraries\QueryParser\Evaluator\Tag\Name;
use App\Libraries\QueryParser\Evaluator\Tag\PropertyExpression;
use App\Libraries\QueryParser\Tag;
use Tests\TestCase;

class TagEvaluatorTest extends TestCase
{
    protected $tagEvaluator;
    protected $queryParser;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->tagEvaluator = new TagEvaluator();
        $this->queryParser = new QueryParser($this, $this);
    }


    public function testTagParsing()
    {
        foreach ($this->dataTagParsing() as $dataItem) {
            $result = $this->tagEvaluator->evaluate($this->createTagFromDataItem($dataItem));
            foreach ($result as $i => $item) {
                $this->assertTrue($item instanceof $dataItem[1][$i]);
                $this->assertEquals($dataItem[2][$i], $item->value);
            }
        }
    }

    public static function dataTagParsing(): array
    {
        return [
            ["<tag>", [Name::class], ['tag']],
            [
                '<actors|index:1::@last_name @first_name|toUpperCase>',
                [Name::class, Method::class, PropertyExpression::class, Method::class],
                ['actors', 'index:1', '@last_name @first_name', 'toUpperCase'],
            ],
            [
                '<actors|index:0::@last_name, @first_name (@role) - @amg_id>',
                [Name::class, Method::class, PropertyExpression::class],
                ['actors', 'index:0', '@last_name, @first_name (@role) - @amg_id'],
            ],
            [
                "<billing_id|regexSearchReplace:'/PO/':'Billing_ID_'|toUpperCase>",
                [Name::class, Method::class, Method::class],
                ["billing_id", "regexSearchReplace:'/PO/':'Billing_ID_'", "toUpperCase"],
            ],
            [
                "<collection|limit:3::@name @last_name|toCommaSeparatedString|toUpperCase>",
                [Name::class, Method::class, PropertyExpression::class, Method::class, Method::class],
                ['collection', 'limit:3', '@name @last_name', 'toCommaSeparatedString', 'toUpperCase'],
            ]
        ];
    }

    protected function createTagFromDataItem(array $dataItem): Tag
    {
        $tags = $this->queryParser->extractTags($dataItem[0]);
        return $tags[0];
    }
}

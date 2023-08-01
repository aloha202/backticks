<?php

namespace App\Libraries\QueryParser\Data;

use App\Libraries\QueryParser\Evaluator\Tag\Name as TagName;
use App\Libraries\QueryParser\Exception\TagRepositoryException;
use Tests\TestCase;
class TagNameEvaluatorTest extends TestCase
{
    protected $tagNameEvaluator;
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->tagNameEvaluator = new TagNameEvaluator($this);
    }

    public function testException1() {
        $this->expectException(TagRepositoryException::class);
        $tagName = new TagName('any');
        $this->tagNameEvaluator->evaluate($tagName);
    }
}

<?php

namespace App\Libraries\QueryParser\Evaluator;

use App\Libraries\QueryParser\Evaluator\Tag\Method;
use App\Libraries\QueryParser\Evaluator\Tag\Name;
use App\Libraries\QueryParser\Evaluator\Tag\PropertyExpression;
use App\Libraries\QueryParser\QueryPreparator;
use App\Libraries\QueryParser\Tag;
use phpDocumentor\Reflection\Types\Self_;

class TagEvaluator extends AbstractEvaluator
{
    public const PROPERTY_EXPRESSION_SEPARATOR = '::';
    public const METHOD_SEPARATOR = '|';

    public function evaluate(Tag $tag, ?QueryPreparator $preparator = null): array
    {
        $exploded = explode(self::PROPERTY_EXPRESSION_SEPARATOR, $tag->value);
        $keys = array_keys($exploded);
        $exploded = array_map(function ($item, $index){
            if ($index) {
                $item = self::PROPERTY_EXPRESSION_SEPARATOR . $item;
            }
            return explode(self::METHOD_SEPARATOR, $item);
        }, $exploded, $keys);
        $exploded = array_merge(...$exploded);
        $keys = array_keys($exploded);

        return array_map(function ($item, $index) use ($preparator){
            if ($index === 0) {
                return new Name($item);
            }
            if (strpos($item, self::PROPERTY_EXPRESSION_SEPARATOR) === 0) {
                return new PropertyExpression(preg_replace(sprintf('/^%s/', self::PROPERTY_EXPRESSION_SEPARATOR), '', $item));
            }

            return new Method($item, $preparator);
        }, $exploded, $keys);
    }
}

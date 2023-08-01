<?php

namespace App\Libraries\QueryParser\Data;

use App\Libraries\QueryParser\Evaluator\Tag\PropertyExpression;
use App\Libraries\QueryParser\Exception\NestedCollectionException;
use App\Libraries\QueryParser\Exception\NotAnObjectException;
use App\Libraries\QueryParser\Exception\UndefinedPropertyException;
use App\Libraries\QueryParser\QueryPreparator;
use Illuminate\Database\Eloquent\Collection;

class PropertyEvaluator
{
    /**
     * @var ?QueryPreparator $preparator
     */
    protected $preparator = null;

    public function __construct(?QueryPreparator $preparator = null) {
        $this->preparator = $preparator;
    }
    public function evaluate(PropertyExpression $propertyExpression, $context, $isNested = false)
    {
        if (!is_object($context) && !is_array($context)) {
            throw new NotAnObjectException("Context is not an object or array for expression: $propertyExpression");
        }

        if (is_array($context) || $context instanceof Collection) {
            if ($isNested) {
                throw new NestedCollectionException("Unabled to evaluate a nested collection of models within a collection of models");
            }
            $result = [];
            foreach($context as $model) {
                $result[] = $this->evaluate($propertyExpression, $model, true);
            }

            return $result;
        }

        $map = [];

        try {
            foreach ($propertyExpression->properties as $property) {
                $map['@' . $property] = $context->{$property};
            }
        } catch (\Exception $e){
            throw new UndefinedPropertyException("Undefined property @{$property}");
        }

        $result = strtr($propertyExpression->value, $map);

        return null !== $this->preparator ? $this->preparator->replaceBack($result) : $result;
    }
}

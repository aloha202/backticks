<?php

namespace App\Libraries\QueryParser\Evaluator\Tag;

class PropertyExpression extends AbstractTagPart
{
    public const PROPERTY_PATTERN = '/@[\w]+/';

    /**
     * @var array<string> $properties
     */
    public $properties = [];
    public function evaluate()
    {
        $this->value = trim($this->value);
        preg_match_all(self::PROPERTY_PATTERN, $this->value, $matches);
        if (count($matches) > 0) {
            $this->properties = array_map(function ($property) {
                return trim($property, '@');
            }, $matches[0]);
        }
    }
}

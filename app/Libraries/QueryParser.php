<?php

namespace App\Libraries;

use App\Libraries\QueryParser\Conditional;
use App\Libraries\QueryParser\Data\MethodEvaluator;
use App\Libraries\QueryParser\Data\PropertyEvaluator;
use App\Libraries\QueryParser\Data\TagNameEvaluator;
use App\Libraries\QueryParser\Evaluator\ConditionalEvaluator;
use App\Libraries\QueryParser\Evaluator\Tag\Method;
use App\Libraries\QueryParser\Evaluator\Tag\Name;
use App\Libraries\QueryParser\Evaluator\Tag\PropertyExpression;
use App\Libraries\QueryParser\Evaluator\TagEvaluator;
use App\Libraries\QueryParser\Exception\BasicQueryParserException;
use App\Libraries\QueryParser\Exception\SystemMethodRepositoryException;
use App\Libraries\QueryParser\Preparator\StringExtractor;
use App\Libraries\QueryParser\Processor\ConditionalProcessor;
use App\Libraries\QueryParser\QueryPreparator;
use App\Libraries\QueryParser\Repository\SystemMethodRepository;
use App\Libraries\QueryParser\Tag;

class QueryParser
{
    public const CONDITIONAL_TAG = '#conditional';
    /**
     * @var array<Tag>
     */
    protected $tags = [];

    /**
     * @var array<Conditional>
     */
    protected $conditionals = [];

    protected QueryPreparator $preparator;
    protected TagEvaluator $tagsEvaluator;
    protected TagNameEvaluator $tagNameEvaluator;
    protected MethodEvaluator $methodEvaluator;
    protected PropertyEvaluator $propertyEvaluator;
    protected ConditionalEvaluator $conditionalEvaluator;

    public function __construct($tagRepository, $methodRepository) {
        $cp = new ConditionalProcessor();
        $this->preparator = new QueryPreparator();
        $this->tagsEvaluator = new TagEvaluator();
        $this->tagNameEvaluator = new TagNameEvaluator($tagRepository);
        $this->methodEvaluator = new MethodEvaluator($methodRepository, $this->preparator, new SystemMethodRepository($cp));
        $this->propertyEvaluator = new PropertyEvaluator();
        $this->conditionalEvaluator = new ConditionalEvaluator($this, $cp);

    }
    public function parse(string $query, $prepare = true)
    {
        $query = trim($query);
        try {
            if ($prepare) {
                $query = $this->preparator->prepareStrings($query);
                $conditionals = $this->extractConditionals($query);
                $query = $this->replaceConditionals($query, $conditionals);
                $query = $this->preparator->prepareOperators($query);
            }
            $tags = $this->extractTags($query);

            foreach ($tags as &$tag) {
                if ($tag->conditional === null) {
                    $elements = $this->tagsEvaluator->evaluate($tag, $this->preparator);
                    $result = null;
                    foreach ($elements as $tagElement) {
                        $result = $this->evaluateTagElement($tagElement, $result);
                    }
                } else {
                    $result = $this->conditionalEvaluator->evaluate($tag->conditional);
                }
                $query = str_replace($tag->raw, $result, $query);
            }

            $result = $this->preparator->replaceBack($query);
            if ($prepare) {
                $this->preparator->clear();
            }

            return $result;
        } catch (BasicQueryParserException $exception) {
            $newMessage = $this->preparator->replaceBack($exception->getMessage());
            $className = get_class($exception);
            throw new $className($newMessage);
        }
    }

    public function evaluateTagElement($tagElement, $context) {
        if ($tagElement instanceof Name) {
            return $this->tagNameEvaluator->evaluate($tagElement);
        }
        if ($tagElement instanceof Method) {
            return $this->methodEvaluator->evaluate($tagElement, $context);
        }
        if ($tagElement instanceof PropertyExpression) {
            return $this->propertyEvaluator->evaluate($tagElement, $context);
        }

        return null;
    }

    /**
     * @param string $query
     * @return array<Tag>
     */
    public function extractTags(string $query, ?Conditional $parent = null): array
    {
        // <directors|limit:3|toCommaSeparatedString::@last_name @first_name|toUpperCase>
        // Result: TESTNOLAN CHRISTOPHER, CAMERON JAMES, JACKSON PETER

        // <actors|index:0::@last_name, @first_name (@role) - @amg_id>
        // Result: Willis, Bruce (Himself) - 2435634

        // <actors|index:1::@last_name @first_name|toUpperCase>
        // Result: WILLIS BRUCE

        // <billing_id|regexSearchReplace:'/PO/':'Billing_ID_'|toUpperCase>==<billing_id|regexCaptureGroup:'/\d{2}$/'>
        // Result: BILLING_ID_2453454==54

        // <genres|limit:3|toCommaSeparatedString::@value_en|toLowerCase>
        // Result: Action, Drama, Adventure -> action, drama, adventure

        preg_match_all('/<([^>]*)>/', $query, $matches);
        if (null === $matches || count($matches) === 0) {
            return [];
        }

        $extracted = [];

        foreach($matches[0] as $index => $value) {
            $tag = new Tag($value, $matches[1][$index]);
            if ($this->isConditionalTag($tag->value)) {
                $tag->conditional = $this->conditionals[$this->getConditionalTagIndex($tag->value)];
            }
            if ($parent !== null) {
                $parent->tags[] = $tag;
            } else {
                $this->tags[] = $tag;
            }
            $extracted[] = $tag;
        }

        return $extracted;
    }

    /**
     * @param string $query
     * @return array<Conditional>
     */
    public function extractConditionals(string $query):array
    {
        // <vendor_id> <slugified_title>++IF(<company|toLowerCase>`==`'epix' && <release_year>`>`1980){'EP_'}++
        // 534632456 Batman_BeginsEP_

        // <vendor_id> <slugified_title>++IF(<company|toLowerCase>`==`'epix' && <release_year>`>`1980){' '<copyright_line>}++
        // 534632456 Batman_Begins @Copyright 2022 Disney LLC
        $pattern = '/\+\+IF(.*?)\+\+/s';
        preg_match_all($pattern, $query, $matches);
        if (null === $matches || count($matches) === 0) {
            return [];
        }

        $extracted = [];

        foreach($matches[0] as $index => $value) {
            $extracted[] = $this->conditionals[$index] = $conditional = new Conditional($value, $matches[1][$index], $index);
            $this->extractTags($conditional->value, $conditional);
        }

        return $extracted;
    }

    /**
     * @param string $query
     * @return array<Tag>
     */
    public function extractConditionalsAndTags(string $query):array
    {
        $conditionals = $this->extractConditionals($query);
        $query = $this->replaceConditionals($query, $conditionals);

        return $this->extractTags($query);
    }

    /**
     * @param string $query
     * @param array<Conditional> $conditionals
     * @return string
     */
    public function replaceConditionals(string $query, array $conditionals):string
    {
        if (count($conditionals) === 0) {
            return $query;
        }

        foreach($conditionals as $conditional) {
            $query = str_replace($conditional->raw, $conditional->tagValue, $query);
        }

        return $query;
    }

    public function _prepareStrings($query) {
        return $this->preparator->prepareStrings($query);
    }

    protected function isConditionalTag(string $tag):bool
    {
        return strpos($tag, QueryParser::CONDITIONAL_TAG) !== false;
    }

    protected function getConditionalTagIndex(string $tagValue): int
    {
        return intval(str_replace(QueryParser::CONDITIONAL_TAG, '', $tagValue));
    }
}

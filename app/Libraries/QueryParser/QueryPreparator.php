<?php

namespace App\Libraries\QueryParser;

use App\Libraries\QueryParser\Preparator\OperatorExtractor;
use App\Libraries\QueryParser\Preparator\StringExtractor;

class QueryPreparator
{
    protected $stringExtractor;
    protected $operatorExtractor;
    public function __construct()
    {
        $this->stringExtractor = new StringExtractor();
        $this->operatorExtractor = new OperatorExtractor();
    }

    /**
     * @param string $query
     * @return string
     */
    public function prepareQuery(string $query): string
    {
        return $this->operatorExtractor->prepareOperators(
            $this->stringExtractor->prepareStringLiterals($query)
        );
    }

    /**
     * @param array|string $query
     * @return array|string
     */
    public function replaceBack($query, $raw = false)
    {
        return $this->operatorExtractor->replaceBack(
            $this->stringExtractor->replaceBack($query, $raw), $raw
        );
    }

    public function prepareStrings($query) {
        return $this->stringExtractor->prepareStringLiterals($query);
    }

    public function prepareOperators($query) {
        return $this->operatorExtractor->prepareOperators($query);
    }

    public function clear() {
        $this->operatorExtractor->clear();
        $this->stringExtractor->clear();
    }

    public function getReplacedOperators()
    {
        return $this->operatorExtractor->getActiveReplacements();
    }

    public function getOperatorByReplacedKey(string $key): ?string
    {
        return $this->operatorExtractor->getByReplacedKey($key);
    }
}

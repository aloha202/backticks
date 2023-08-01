<?php

namespace App\Libraries\QueryParser\Preparator;

class StringExtractor implements PreparatorInterface
{
    protected $_literals = [];

    protected $_prepared = [];

    /**
     * @param string $query
     * @return string
     */
    public function prepare(string $query): string
    {
        return $this->prepareStringLiterals($query);
    }

    public function extractStringLiterals($query):array {

        $pattern = "/'(.*?(?<!\\\\)(\\\\\\\\)*)'/";
        preg_match_all($pattern, $query, $matches);

        return count($matches) > 0 ? $matches[0] : [];
    }

    public function prepareStringLiterals($query): string
    {
        $literals = $this->extractStringLiterals($query);
        $replacements = [];
        foreach($literals as $i => $literal) {
            $name = $this->makeName($i);
            $this->_literals[$name] = $literal;
            $this->_prepared[$name] = $this->make($literal);
            $replacements[$literal] = $name;
        }
        return strtr($query, $replacements);
    }

    /**
     * @param string|array $query
     * @param bool $raw
     * @return string|array
     */
    public function replaceBack($query, bool $raw = false)
    {
        $result = '';
        $map = $raw ? $this->_literals : $this->_prepared;
        if (is_string($query)) {
            $result = strtr($query, $map);
        } else if (is_array($query)) {
            $result = array_map(function($item) use($map){
                return strtr($item, $map);
            }, $query);
        }

        return $result;
    }

    public function makeName($index) {
        return "[[[[[[###{$index}]]]]]]";
    }

    public function clear() {
        $this->_literals = [];
        $this->_prepared = [];
    }

    protected function make(string $string): string
    {
        return str_replace("\'", "'", preg_replace('/^\'|\'$/', '', $string));
    }
}

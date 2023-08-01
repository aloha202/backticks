<?php

namespace App\Libraries\QueryParser\Preparator;

use App\Libraries\QueryParser\Exception\InvalidOperatorException;

class OperatorExtractor implements PreparatorInterface
{
    public const REPLACE_MODE_RAW = true;

    public const REPLACE_MODE_VALUE = false;

    public const REPLACE_MODE_OBJECT = 'object';
    protected static $_operators = [
        'if' => 'if',
        '>' => 'gt',
        '<' => 'lt',
        '=' => 'eq',
        '!=' => 'ne',
        '<>' => 'ne2',
        'or' => 'or',
        'else' => 'else',
        'elseif' => 'elseif',
        'and' => 'and',
        'then' => 'then',
        '<=' => 'lte',
        '>=' => 'gte',
        '==' => 'eq2',
    ];

    protected $_prepared = [];

    protected $_literals = [];

    protected $_objects = [];

    /**
     * @param string $query
     * @return string
     */
    public function prepareOperators(string $query): string
    {
        $pattern = "/`([^`]+)`/";
        preg_match_all($pattern, $query, $matches);
        $map = [];
        $_operators = array_keys(self::$_operators);
        if (count($matches) && count($matches[0])) {
            foreach($matches[0] as $i => $rawValue) {
                $value = strtolower($matches[1][$i]);
                if (in_array($value, $_operators) === false) {
                    throw new InvalidOperatorException("Invalid operator $rawValue");
                }
                $value = self::$_operators[$value];
                $name = $this->makeName($value);
                $map[$rawValue] = $name;
                $this->_prepared[$name] = $value;
                $this->_literals[$name] = $rawValue;
                $this->_objects[$name] = new Operator($rawValue, $name, $value);
            }
        }

        return strtr($query, $map);
    }

    public function prepare(string $query): string {
        return $this->prepareOperators($query);
    }

    /**
     * @param array|string $query
     * @param bool $raw
     * @return array|string
     */
    public function replaceBack($query, bool $raw = false) {

        $map = $raw ? $this->_literals : $this->_prepared;
        $result = '';
        if (is_string($query)) {
            $result = $this->replaceSubstring($query, $map);
        } else if (is_array($query)) {
            $result = array_map(function ($item) use ($map) {
                return $this->replaceSubstring($item, $map);
            }, $query);
        }

        return $result;
    }

    public function replaceSubstring($query, $map)
    {
        foreach($this->_objects as $name => $operator) {
            if ($query === $name) {
                return $operator;
            }
        }

        return strtr($query, $map);
    }

    public function clear()
    {
        $this->_literals = [];
        $this->_prepared = [];
    }

    public function makeName($operator) {
        return  "[[[[[;;;;;{$operator}]]]]]";
    }

    public function getActiveReplacements(): array
    {
        return array_keys($this->_prepared);
    }

    public function getByReplacedKey(string $key): ?string
    {
        return $this->_prepared[$key] ?? null;
    }

    public static function getBacktickedOperatorValue(string $backticked): ?string
    {
        $key = preg_replace('/^`|`$/', '', $backticked);

        return array_key_exists($key, self::$_operators) ? self::$_operators[$key] : null;
    }


}

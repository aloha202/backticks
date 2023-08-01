<?php

namespace App\Libraries\QueryParser\Evaluator\Tag;

use App\Libraries\QueryParser\Data\Variable;
use App\Libraries\QueryParser\Exception\InvalidMethodNameException;
use App\Libraries\QueryParser\Exception\InvalidMethodParameterException;
use App\Libraries\QueryParser\Preparator\Operator;
use App\Libraries\QueryParser\Preparator\OperatorExtractor;

class Method extends AbstractTagPart
{
    public const PARAMETERS_DELIMITER = ':';
    public $methodName;
    public $parameters = [];
    public $all = [];
    protected $_system = ['if'];

    /**
     * @return $this
     * @throws InvalidMethodNameException
     * @throws InvalidMethodParameterException
     */
    public function evaluate(): self
    {
        $exploded = explode(self::PARAMETERS_DELIMITER, $this->value);
        $keys = array_keys($exploded);
        $exploded = array_map(function ($item, $key){
            $param = trim($item);

            if ($key && $this->isVariable($param)) {
                return new Variable($param);
            }

            if ($param === '') {
                $value = null !== $this->preparator ? $this->preparator->replaceBack($this->value, true) : $this->value;
                if ($key === 0) {
                    throw new InvalidMethodNameException("Invalid method name found in '{$value}'");
                } else {
                    throw new InvalidMethodParameterException("Invalid parameter found in '{$value}'");
                }
            }
            if (preg_match('/^-?\d+$/', $param) === 1) {
                return intval($param);
            }

            return null !== $this->preparator ? $this->preparator->replaceBack($param) : $param;
        }, $exploded, $keys);
        $methodName = array_shift($exploded);
        $this->methodName = is_object($methodName) ? $methodName->value : $methodName;

        if (in_array(strtolower($this->methodName), $this->_system) === true) {
            $this->methodName = strtolower($this->methodName);
        }

        if ($this->isInvalidMethodName()) {
            throw new InvalidMethodNameException("Method name '{$this->methodName}' is invalid");
        }

        $this->parameters = $exploded;

        $this->all = array_merge([$this->methodName], $this->parameters);

        return $this;
    }

    protected function isVariable(string $param): bool
    {
        return preg_match(Variable::PREG_PATTERN, $param) === 1;
    }

    protected function isInvalidMethodName(): bool
    {
        $invalid = $this->methodName === ''
            || preg_match('/^[a-zA-Z0-9_]+$/', $this->methodName) == false;

        return $invalid;
    }

    public function isSystem(): bool
    {
        return in_array($this->methodName, $this->_system);
    }

    public function hasOperators(): bool
    {
        foreach($this->parameters as $parameter) {
            if ($parameter instanceof Operator) {
                return true;
            }
        }

        return false;
    }
}

<?php

namespace App\Libraries\QueryParser\Processor;

use App\Libraries\QueryParser\Preparator\Operator;
use App\Libraries\QueryParser\Processor\Exception\BadOperatorException;
use App\Libraries\QueryParser\Processor\Exception\ConditionalProcessorException;
use App\Libraries\QueryParser\Processor\Exception\DoubleOperatorException;
use App\Libraries\QueryParser\Processor\Exception\InvalidConditionalOperatorException;
use App\Libraries\QueryParser\Processor\Exception\MissingLeftPartException;
use App\Libraries\QueryParser\Processor\Exception\NotEnoughParametersException;
use App\Libraries\QueryParser\Processor\Exception\ThenIsMissingException;

class ConditionalProcessor
{
    public function process($parameters)
    {
        $this->validate($parameters);

        $result = $this->execute($parameters);

        return $result;
    }

    public function validate($parameters) {
        if (count($parameters) < 1) {
            throw new NotEnoughParametersException("Not enough parameters");
        }

        if ($parameters[0] instanceof Operator) {
            throw new ConditionalProcessorException("Malformed conditional: first parameter can not be operator");
        }

        $then = array_filter($parameters, function ($item){
            return $item instanceof Operator && $item->value === 'then';
        });
        if (count($then) === 0) {
            throw new ThenIsMissingException("Then is missing in conditional");
        }

        if ($parameters[count($parameters) - 1] instanceof Operator) {
            throw new BadOperatorException(sprintf("Unresolved operator: '%s'", $parameters[count($parameters) - 1]->value));
        }

        $previous = null;
        foreach($parameters as $item) {
            if (null !== $previous) {
                if ($previous instanceof Operator && $item instanceof Operator) {
                    throw new DoubleOperatorException(sprintf("Double operator: '%s' '%s'", $previous->value, $item->value));
                }
            }
            $previous = $item;
        }



    }

    protected function execute($parameters)
    {
        $index = 0;
        $_left = [$index => []];
        $_then = [$index => []];
        $_else = [];
        $isThenFound = false;
        $isElseFound = false;
        while (count($parameters)) {
            $item = array_shift($parameters);
            if ($this->_isThen($item)) {
                $isThenFound = true;
                continue;
            }
            if ($isThenFound === false) {
                $_left[$index][] = $item;
            } else {
                if ($this->_isElse($item)) {
                    $isElseFound = true;
                    continue;
                }
                if ($this->_isElseIf($item)) {
                    $isThenFound = false;
                    $isElseFound = false;
                    $index++;
                    continue;
                }
                if ($isElseFound === false) {
                    $_then[$index][] = $item;
                } else {
                    $_else[] = $item;
                }
            }
        }

        for ($i = 0; $i < $index + 1; $i++) {
            if ($this->evaluateLeft($_left[$i])) {
                return count($_then[$i]) ? array_shift($_then[$i]) : null;
            }
        }

        return count($_else) ? array_shift($_else) : null;
    }

    public function evaluateLeft(array $params) {
        if (count($params) === 0) {
            throw new MissingLeftPartException("Missing left part for conditional");
        }
        $topLevel = [];
        $left = null;
        $operator = null;
        $right = null;
        $result = null;
        while(count($params)) {
            $item = array_shift($params);

            if ($this->_isAnd($item) || $this->_isOr($item)) {
                $result = $this->_evaluate($left, $operator, $right);
                $topLevel[] = $result;
                $topLevel[] = $item;
                $left = null;
                $operator = null;
                $right = null;
                $result = null;
                continue;
            }

            if ($left === null) {
                $left = $item;
                continue;
            }
            if ($operator === null) {
                $operator = $item;
                continue;
            }
            if ($right === null) {
                $right = $item;
                continue;
            }
        }

        $result = $this->_evaluate($left, $operator, $right);

        if (count($topLevel) === 0) {
            return $result;
        }

        $topLevel[] = $result;

        return $this->evaluateTopLevel($topLevel);
    }

    public function evaluateTopLevel($params) {
        $result = null;
        $operator = null;
        while(count($params)) {
            $item = array_shift($params);
            if ($result === null) {
                $result = $item;
                continue;
            }
            if ($operator === null) {
                $operator = $item;
                continue;
            }
            $result = $this->_evaluate($result, $operator, $item);
            $operator = null;
        }

        return $result;
    }

    public function evaluateItem($left, $op = null, $right = null)
    {
        return $this->_evaluate($left, $op, $right);
    }

    protected function _evaluate($left, $operator = null, $right = null)
    {
        if (null === $left) {
            throw new NotEnoughParametersException("Left parameter is undefined");
        }
        if (null === $operator && null === $right) {

            return !!$left;
        }
        $this->_validateOperator($operator);

        if (null === $right) {
            throw new NotEnoughParametersException("Right parameter is undefined");
        }

        $method = '_' . $operator->value;

        return $this->{$method}($left, $right);
    }

    protected function _isThen($item):bool {
        return $item instanceof Operator
            && $item->value === 'then';
    }

    protected function _isElse($item):bool {
        return $item instanceof Operator
            && $item->value === 'else';
    }

    protected function _isElseIf($item):bool {
        return $item instanceof Operator
            && $item->value === 'elseif';
    }

    protected function _isAnd($item):bool {
        return $item instanceof Operator
            && $item->value === 'and';
    }

    protected function _isOr($item):bool {
        return $item instanceof Operator
            && $item->value === 'or';
    }

    protected function _isGt($item):bool {
        return $item instanceof Operator
            && $item->value === 'gt';
    }

    protected function _isLt($item):bool {
        return $item instanceof Operator
            && $item->value === 'lt';
    }

    protected function _isEq($item):bool {
        return $item instanceof Operator
            && ($item->value === 'eq' || $item->value === 'eq2');
    }

    protected function _isNe($item):bool {
        return $item instanceof Operator
            && ($item->value === 'ne' || $item->value === 'ne2');
    }

    protected function _isLte($item):bool {
        return $item instanceof Operator
            && $item->value === 'lte';
    }

    protected function _isGte($item):bool {
        return $item instanceof Operator
            && $item->value === 'gte';
    }

    protected function _validateOperator($operator): void
    {
        if ($this->_isEq($operator) === false
                && $this->_isNe($operator) === false
            && $this->_isGt($operator) === false
            && $this->_isLt($operator) === false
            && $this->_isLte($operator) === false
            && $this->_isGte($operator) === false
            && $this->_isAnd($operator) === false
            && $this->_isOr($operator) === false) {

            throw new InvalidConditionalOperatorException($operator->value);
        }
    }

    protected function _eq($left, $right) {
        return $left == $right;
    }

    protected function _eq2($left, $right) {
        return $this->_eq($left, $right);
    }

    protected function _ne($left, $right) {
        return $left != $right;
    }

    protected function _ne2($left, $right)
    {
        return $this->_ne($left, $right);
    }

    protected function _lt($left, $right) {
        return $left < $right;
    }

    protected function _gt($left, $right) {
        return $left  > $right;
    }

    protected function _gte($left, $right) {
        return $left >= $right;
    }

    protected function _lte($left, $right)
    {
        return $left <= $right;
    }

    protected function _and($left, $right) {
        return $left && $right;
    }

    protected function _or($left, $right) {
        return $left || $right;
    }

}

<?php

namespace App\Libraries\QueryParser\Data;

use App\Libraries\QueryParser\Evaluator\Tag\Method;
use App\Libraries\QueryParser\Exception\InvalidMethodNameException;
use App\Libraries\QueryParser\Exception\MethodRepositoryException;
use App\Libraries\QueryParser\Exception\NonSystemMethodWithOperatorsException;
use App\Libraries\QueryParser\Exception\SystemMethodRepositoryException;
use App\Libraries\QueryParser\Exception\UndefinedVariableException;
use App\Libraries\QueryParser\QueryPreparator;

class MethodEvaluator
{
    protected $methodRepository;

    protected $systemMethodRepository;

    protected $_system_methods = [
        'if'
    ];

    /**
     * @var ?QueryPreparator $preparator
     */
    protected $preparator = null;

    protected $context = null;

    public function __construct(
        $methodRepository,
        ?QueryPreparator $preparator = null,
        $systemMethodRepository = null
    )
    {
        $this->setMethodRepository($methodRepository);
        $this->preparator = $preparator;
        $this->setSystemMethodRepository($systemMethodRepository);
    }

    public function setMethodRepository($methodRepository)
    {
        $this->methodRepository = $methodRepository;
    }

    public function setSystemMethodRepository($systemMethodRepository)
    {
        $this->systemMethodRepository = $systemMethodRepository;
    }

    /**
     * @param Method $method
     * @param $context
     * @return mixed
     * @throws MethodRepositoryException
     * @throws NonSystemMethodWithOperatorsException
     */
    public function evaluate(Method $method, $context)
    {
        $this->context = $context;
        if ($method->isSystem()) {
            return $this->evaluateSystem($method, $context);
        }
        if ($method->hasOperators()) {
            throw new NonSystemMethodWithOperatorsException("Non-system function '{$method->methodName}' can not use operators");
        }

        $parameters = $this->makeParameters($method->parameters);

        try {
            return $this->methodRepository->callFunction($method->methodName, array_merge([$context], $parameters));
        } catch (\Throwable $e) {
            throw new MethodRepositoryException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function evaluateSystem(Method $method, $context)
    {
        return $this->systemMethodRepository->callSystemMethod($method->methodName, array_merge([$context], $this->makeParameters($method->parameters)));
    }

    public function makeParameters(array $parameters): array
    {
        return array_map(function($item) {
            return $item instanceof Variable ? $this->evaluateVariable($item) : $item;
        }, $parameters);
    }

    public function evaluateVariable(Variable $variable)
    {
        if (in_array($variable->value, ['@this', '@context'])) {
            return $this->context;
        }

        throw new UndefinedVariableException(sprintf("Undefined variable '%s'", $variable->value));
    }
}

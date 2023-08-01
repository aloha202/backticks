<?php

namespace App\Libraries\QueryParser\Repository;

use App\Libraries\QueryParser\Exception\SystemMethodRepositoryException;
use App\Libraries\QueryParser\Exception\UndefinedSystemMethodException;
use App\Libraries\QueryParser\Preparator\Operator;
use App\Libraries\QueryParser\Processor\ConditionalProcessor;
use App\Libraries\QueryParser\Processor\Exception\ConditionalProcessorException;

class SystemMethodRepository
{
    public const IF_NULL = '';
    protected $_map = [
        'if' => '_if'
    ];

    public function __construct(protected ConditionalProcessor $conditionalProcessor)
    {
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return mixed
     * @throws SystemMethodRepositoryException
     * @throws UndefinedSystemMethodException
     */
    public function callSystemMethod(string $name, array $parameters)
    {
        if (!array_key_exists($name, $this->_map)) {
            throw new UndefinedSystemMethodException("Undefined system method '$name'");
        }

        $method = $this->_map[$name];

        try {
            return $this->{$method}(...$parameters);
        } catch (\Throwable $e) {
            if ($e instanceof ConditionalProcessorException) {
                throw $e;
            }
            throw new SystemMethodRepositoryException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function _if(...$arguments) {
        $result = $this->conditionalProcessor->process($this->normalize_conditional_arguments($arguments));

        return null === $result ? self::IF_NULL : $result;
    }

    protected function normalize_conditional_arguments($args)
    {
        $normalized = [];
        $context = $args[0];
        if ($args[0] === $args[1]) {
            array_shift($args);
        }
        for($i = 0; $i  < count($args); $i++) {
            $item = $args[$i];
            $normalized[] = $item;
            if ($item instanceof Operator && in_array($item->value, ['elseif', 'and', 'or'])) {
                if (array_key_exists($i + 1, $args)) {
                    if ($args[$i + 1] instanceof Operator && $args[$i + 1]->isMathematical()) { // then the starting value for elseif, and. or is missing
                        $normalized[] = $context;
                    }
                }
            }
        }

        return $normalized;
    }
}

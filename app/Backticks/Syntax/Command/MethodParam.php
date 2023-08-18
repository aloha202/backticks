<?php

namespace App\Backticks\Syntax\Command;

use App\Backticks\Syntax\Entity\PositionEntity;
use App\Backticks\Syntax\Exceptions\InvalidCommandHeadException;
use App\Backticks\Syntax\Exceptions\InvalidMethodParameterException;

class MethodParam
{
    public string $value;
    public int $trimOffset;
    public function __construct(
        public string $rawValue,
        public Method $method,
        public ?PositionEntity $positionEntity = null,
    )
    {
        $this->value = trim($this->rawValue);
        $this->trimOffset = strpos($this->rawValue, $this->value);
        $this->validate();
    }

    protected function validate(): void
    {
        if ($this->value === '') {
            throw new InvalidMethodParameterException("Method parameter can not be blank", $this->getFullPos());
        }
    }

    public function getPos(): int
    {
        return ($this->positionEntity?->originalPosition ?? 0) + $this->trimOffset;
    }

    public function getFullPos(): int
    {
        return $this->method->getFullPos() + $this->getPos();
    }
}

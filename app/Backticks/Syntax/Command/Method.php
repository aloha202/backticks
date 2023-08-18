<?php

namespace App\Backticks\Syntax\Command;

use App\Backticks\Syntax\Exceptions\InvalidMethodException;

class Method extends AbstractCommandPart
{
    public const PARAM_DELIMITER = ':';

    public string $name;
    public string $paramsString;
    public array $params;
    public int $nameDelta;
    /**
     * @var array<MethodParam>
     */
    public array $parameters = [];
    protected function evaluate(): void
    {
        $exploded = explode(':', $this->value);
        $name = array_shift($exploded);
        $this->name = trim($name);
        $this->params = $exploded;
        $this->nameDelta = strlen($name) + 1;
        $this->paramsString = substr_replace($this->value, '', 0, $this->nameDelta);

        $this->validate();
    }

    protected function validate(): void
    {
        if ($this->value === '') {
            throw new InvalidMethodException('Method can not be empty', $this->getFullPos());
        }
        if (preg_match("/^[a-zA-Z0-9_]+$/", $this->name) !== 1) {
            throw new InvalidMethodException('Invalid  characters in method name', $this->getFullPos());
        }
    }
}

<?php

namespace App\Backticks\Syntax\Exceptions;

use App\Backticks\Exceptions\BacktickException;
use Throwable;

class BackticksSyntaxErrorException extends BacktickException
{
    protected ?int $position;

    public function __construct(string $message, int $position = null, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition(int $position)
    {
        $this->position = $position;
    }
}

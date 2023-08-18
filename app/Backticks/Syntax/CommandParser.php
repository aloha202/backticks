<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Command\Head;
use App\Backticks\Syntax\Command\Method;
use App\Backticks\Syntax\Command\MethodParam;
use App\Backticks\Syntax\Entity\Command;
use App\Backticks\Syntax\Entity\PositionEntity;

class CommandParser
{
    public const DELIMITER = '|';

    protected int $delta = 0;
    protected int $realDelta = 0;

    protected int $deltaParam = 0;
    protected int $realDeltaParam = 0;

    public function __construct(protected ?PositionManager $positionManager = null)
    {

    }

    public function setPositionManager(PositionManager $positionManager)
    {
        $this->positionManager = $positionManager;
    }

    public function parse(Command $command): Command
    {
        $string = $command->value;
        $exploded = explode(self::DELIMITER, $command->value);
        $strHead = array_shift($exploded);
        $head = new Head($strHead, $command, $this->_position($strHead, $string));
        $methods = array_map(function (string $item) use ($command, &$string){
            $method = new Method($item, $command, $this->_position($item, $string));
            return $this->parseMethod($method);
        }, $exploded);
        $command->parts = array_merge([$head], $methods);
        $command->head = $head;
        $command->methods = $methods;

        $this->delta = 0;
        $this->realDelta = 0;

        return $command;
    }

    public function parseMethod(Method $method): Method
    {
        $string = $method->paramsString;
        $method->parameters = array_map(function (string $param) use (&$string, $method) {
            return new MethodParam($param, $method, $this->_positionParam($param, $string, $method->nameDelta));
        }, $method->params);

        $this->deltaParam = 0;
        $this->realDeltaParam = 0;

        return $method;
    }

    public function _position($substr, &$string): ?PositionEntity
    {
        if (null === $this->positionManager) {
            return null;
        }

        $pos = strpos($string, $substr);
        $len = strlen($substr);
        $delimLen = strlen(self::DELIMITER);
        $realPos = $this->positionManager->_pos($string, $substr);
        $realLen = $this->positionManager->_strlen($substr);

        $position = new PositionEntity(
            $substr,
            $realPos + $this->realDelta,
            $realLen,
            $pos + $this->delta,
            $len,
        );
        $this->delta += $len + $delimLen;
        $this->realDelta += $realLen + $delimLen;

        $string = substr_replace($string, '', 0, $len + $delimLen);

        return $position;
    }

    public function _positionParam($substr, &$string, $nameDelta): ?PositionEntity
    {
        if (null === $this->positionManager) {
            return null;
        }

        $pos = strpos($string, $substr);
        $len = strlen($substr);
        $delimLen = $delimLen ?? strlen(Method::PARAM_DELIMITER);
        $realPos = $this->positionManager->_pos($string, $substr);
        $realLen = $this->positionManager->_strlen($substr);

        $position = new PositionEntity(
            $substr,
            $realPos + $this->realDeltaParam + $nameDelta,
            $realLen + $nameDelta,
            $pos + $this->deltaParam + $nameDelta,
            $len + $nameDelta,
        );
        $this->deltaParam += $len + $delimLen;
        $this->realDeltaParam += $realLen + $delimLen;

        $string = substr_replace($string, '', 0, $len + $delimLen);

        return $position;
    }
}

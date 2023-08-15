<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Entity\PositionEntity;
use App\Backticks\Syntax\Entity\StructureEntity;
use App\Backticks\Syntax\Structure\Command;

class StructureParser
{
    public const DELIM = '~';

    public array $_commands = [];

    public function __construct(
        public ?PositionManager $positionManager = null,
    ) {

    }

    public function setPositionManager(PositionManager $positionManager)
    {
        $this->positionManager = $positionManager;
    }

    public function parse(StructureEntity $structureEntity)
    {
        $currentPos = 0;
        $deltaLen = strlen(self::DELIM);
        $string = $structureEntity->value;
        $exploded = explode(self::DELIM, $string);
        foreach($exploded as $i => $value) {
            $pos = strpos($string, $value);
            $realPos = $pos + $currentPos;
            $len = strlen($value);
            $command = new Command(
                $value,
                $this->_position($realPos, $value, $structureEntity->value),
                $structureEntity,
            );
            $structureEntity->_commands[] = $command;
            $this->_commands[] = $command;

            $string = substr_replace($string, '', $pos, $len + $deltaLen);
            $currentPos += $len + $deltaLen;
        }
    }

    protected function _position(int $pos, string $value, string $string): ?PositionEntity
    {
        if (null === $this->positionManager) {
            return null;
        }

        $realPos = $this->positionManager->getRealPos($pos, $string);
        $len = $this->positionManager->_strlen($value);

        $position = new PositionEntity(
            $value,
            $realPos,
            $len,
            $realPos,
            $len,
        );

        return $position;
    }

    public function clear()
    {
        $this->_commands = [];
    }

}

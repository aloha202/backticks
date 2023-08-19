<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Entity\PositionEntity;
use App\Backticks\Syntax\Entity\StructureEntity;
use App\Backticks\Syntax\Entity\Command;

class StructureParser
{
    public const DELIM = '~';

    public array $_commands = [];

    public function __construct(
        public ?PositionManager $positionManager = null,
        public ?CommandParser $commandParser = null,
    ) {

    }

    public function setPositionManager(PositionManager $positionManager)
    {
        $this->positionManager = $positionManager;
    }

    public function setCommandParser(CommandParser $commandParser)
    {
        $this->commandParser = $commandParser;
        if (null !== $this->positionManager) {
            $this->commandParser->setPositionManager($this->positionManager);
        }
    }

    public function getCommandParser(): ?CommandParser
    {
        return $this->commandParser;
    }

    public function parse(StructureEntity $structureEntity): StructureEntity
    {
        $currentPos = 0;
        $deltaLen = strlen(self::DELIM);
        $string = $structureEntity->preparedValue;

        $exploded = explode(self::DELIM, $string);
        foreach($exploded as $i => $value) {
            $pos = strpos($string, $value);
            $realPos = $pos + $currentPos;
            $len = strlen($value);
            /**
             * @TODO fix possible bug with command position for identical commands
             */
            $command = new Command(
                $value,
                $this->_position($realPos, $value, $structureEntity->preparedValue),
                $structureEntity,
            );

            if (null !== $this->commandParser) {
                $this->commandParser->parse($command);
            }

            $structureEntity->_commands[] = $command;
            $this->_commands[] = $command;

            $string = substr_replace($string, '', $pos, $len + $deltaLen);
            $currentPos += $len + $deltaLen;
        }

        return $structureEntity;
    }

    public function parseSingleCommand(Command $command): Command
    {
        return $this->commandParser->parse($command);
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

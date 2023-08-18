<?php

namespace App\Backticks\Syntax\Entity;

class StructureEntity extends SyntaxEntity
{
    /**
     * @var array<Command>
     */
    public array $_commands = [];

    public int $level = 1;

    /**
     * @var array<SubstructureEntity>
     */
    public array $_substructures = [];

    public string $preparedValue;

    public function __construct(string $raw, string $value, string $name, ?PositionEntity $positionEntity = null)
    {
        parent::__construct($raw, $value, $name, $positionEntity);

        $this->preparedValue = $this->value;
    }

    public function getLeftOffset()
    {
        return 2 * $this->level;
    }

    /**
     * @param false $sort
     * @return array<Command>
     */
    public function getAllCommands($sort = false): array {
        $commands = array_merge($this->_commands, array_map(function(SubstructureEntity $sub){
            return $sub->_command;
        }, $this->_substructures));

        if ($sort) {
            usort($commands, function (Command $a, Command $b) {
                return $a->getFullPos() - $b->getFullPos();
            });
        }

        return $commands;
    }
}

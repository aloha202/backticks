<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Entity\PositionEntity;

class PositionManager
{
    /**
     * @var array<PositionEntity>
     */
    protected array $_entities = [];

    public function add(PositionEntity $entity)
    {
        $this->_entities[] = $entity;
    }

    public function _pos(string $string, string $match): int
    {
        $pos = strpos($string, $match);

        return $this->getRealPos($pos, $string);
    }

    public function getRealPos(int $pos, string $string): int
    {
        $left = substr($string, 0, $pos);
        foreach($this->_entities as $entity) {
            if (str_contains($left, $entity->name)) {
                $pos -= $entity->delta;
            }
        }

        return $pos;
    }

    public function _strlen(string $match): int
    {
        $len = strlen($match);

        foreach ($this->_entities as $entity) {
            if (str_contains($match, $entity->name)) {
                $len -= $entity->delta;
            }
        }

        return $len;
    }

    public function clear()
    {
        $this->_entities = [];
    }
}

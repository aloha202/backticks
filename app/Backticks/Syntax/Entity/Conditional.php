<?php

namespace App\Backticks\Syntax\Entity;

class Conditional extends Command
{
    public static array $_elements = [
        '<=', '=>', '||', '&&', '<>', '=', '<', '>'
    ];

    /**
     * @var array<ConditionalGroupEntity>
     */
    public array $_groups = [];
    /**
     * @var array<Command>
     */
    public array $_commands = [];
    /**
     * @var array<Operator>
     */
    public array $_operators = [];
    public ConditionalGroupEntity $rootGroup;
    public string $replacedValue;

    public static function isConditionalValue($str): bool
    {
        foreach(self::$_elements as $element)
        {
            if (str_contains($str, $element)) {
                return true;
            }
        }

        return false;
    }

    public function getGroupByName($name): ?ConditionalGroupEntity {

        foreach($this->_groups as $group) {
            if ($group->name === $name) {
                return $group;
            }
        }

        return null;
    }

    public function getAllParts(): array
    {
        return $this->getGroupParts($this->rootGroup);
    }

    protected function getGroupParts(ConditionalGroupEntity $groupEntity): array
    {
        return array_map(function($item) {
            if ($item instanceof ConditionalGroupEntity) {
                return $this->getGroupParts($item);
            }
            return $item;
        }, $groupEntity->_parts);
    }


}

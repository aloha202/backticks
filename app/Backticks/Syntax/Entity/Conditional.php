<?php

namespace App\Backticks\Syntax\Entity;

class Conditional extends Command
{
    public static array $_elements = [
        '<=', '=>', '||', '&&', '<>', '=', '<', '>'
    ];

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
}

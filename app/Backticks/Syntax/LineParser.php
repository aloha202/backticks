<?php

namespace App\Backticks\Syntax;

class LineParser
{
    protected array $splitted = [];
    public function parse(string $string)
    {
        $this->splitted = explode("\n", $string);
    }

    public function getLine(int $pos): ?int
    {
        $len = 0;
        foreach($this->splitted as $line => $string)
        {
            $lineLen = strlen($string) + 1;
            if ($pos >= $len && $pos < $len + $lineLen) {
                return $line;
            }
            $len += $lineLen;
        }

        return null;
    }
}

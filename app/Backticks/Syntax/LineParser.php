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
        $linePos = $this->getLineAndPositionInLine($pos);
        return null === $linePos ? null : $linePos[0];
    }

    public function getPositionInLine(int $pos): ?int
    {
        $linePos = $this->getLineAndPositionInLine($pos);
        return null === $linePos ? null : $linePos[1];
    }

    public function getLineAndPositionInLine(int $pos): ?array
    {
        $len = 0;
        foreach($this->splitted as $line => $string)
        {
            $lineLen = strlen($string) + 1;
            if ($pos >= $len && $pos < $len + $lineLen) {
                return [$line, $pos - $len];
            }
            $len += $lineLen;
        }

        return null;
    }
}

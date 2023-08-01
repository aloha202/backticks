<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Exceptions\ParseErrorException;
use App\Backticks\Syntax\StructureExtractor\DTO\Config;

class StructureExtractor
{
    public const PREG_STRUCTURE = "/`~([^`]*)~`/s";

    public int $level = 0;

    protected array $_replaced = [];

    public function __construct(
        protected ?Config $config = null,
    ){
        if (null === $this->config) {
            $this->config = new Config();
        }
    }

    public function extractStructures($string, $level = null): string
    {
        $iteration = $this->level = 0;
        $i = 1;
        $matches = $this->matchStructures($string);
        while($matches !== null && count($matches) && is_array($matches[0]) && count($matches[0]))
        {
            $iteration++;
            $replacements = [];
            foreach($matches[0] as $match)
            {
               // $value = $matches[1][$i];
                if (array_key_exists($match, $replacements) === false) {
                    $name = $this->makeStructureName($i);
                    $replacements[$match] = $name;
                    $this->_replaced[$name] = $match;
                    $i++;
                }
            }

            $string = strtr($string, $replacements);
            $this->level = $iteration;
            if (null !== $level && $iteration >= $level) {
                break;
            }
            $matches = $this->matchStructures($string);
        }

        if (null === $level && (str_contains($string, '`~') || str_contains($string, '~`'))) {
            throw new ParseErrorException("Could not parse, check opening & closing tags: '$string'");
        }

        return $string;
    }

    public function replaceBack($result)
    {
        do {
            $found = false;
            foreach($this->_replaced as $name => $value) {
                if (str_contains($result, $name)) {
                    $result = str_replace($name, $value, $result);
                    $found = true;
                }
            }
        } while($found);

        return $result;
    }

    public function clear()
    {
        $this->_replaced = [];
    }

    public function makeStructureName($index)
    {
        return $this->config->structureLeftHash
            . $this->config->structureLeftAdditional
            . $index
            . $this->config->structureRightAdditional
            . $this->config->structureRightHash;
    }

    public function matchStructures($string): ?array
    {
        preg_match_all(self::PREG_STRUCTURE, $string, $matches);

        return $matches;
    }


    public function setConfig(Config $config): void
    {
        $this->config = $config;
    }
}

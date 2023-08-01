<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Preprocessor\DTO\Config;

class Preprocessor
{
    public const PREG_STRUCTURE = "/`~([^`~]*)~`/s";
    public function __construct(
        protected ?Config $config = null,
    ){
        if (null === $this->config) {
            $this->config = new Config();
        }
    }

    public function extractStrings(){

    }



    public function extractStructures($string, $level = null): string
    {
        $iteration = 0;
        $i = 1;
        while($matches = $this->matchStructures($string))
        {
            $iteration++;
            $replacements = [];
            foreach($matches[0] as $match)
            {
               // $value = $matches[1][$i];
                if (array_key_exists($match, $replacements) === false) {
                    $name = $this->makeStructureName($i);
                    $replacements[$match] = $name;
                    $i++;
                }
            }

            $string = strtr($string, $replacements);
            if (null !== $level && $iteration >= $level) {
                break;
            }
        }

        return $string;
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

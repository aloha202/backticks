<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\DTO\StringExtractorConfig;
use App\Backticks\Syntax\Exceptions\ParseErrorException;

class StringExtractor
{
    public const PREG_STRINGS = "/'(.*?(?<!\\\\)(\\\\\\\\)*)'/s";

    protected array $_values = [];
    protected array $_literal = [];

    public function __construct(protected ?StringExtractorConfig $config = null)
    {
        if (null === $this->config)
        {
            $this->config = new StringExtractorConfig();
        }
    }

    public function setConfig(StringExtractorConfig $config): void
    {
        $this->config = $config;
    }

    public function matchStrings(string $string): array
    {
        preg_match_all(self::PREG_STRINGS, $string, $matches);

        return $matches;
    }

    public function extractStrings(string $string): string
    {
        $matches = $this->matchStrings($string);

        $index = 1;
        if (is_array($matches) && is_array($matches[0])) {
            $map = [];
            foreach($matches[0] as $i => $match) {
                if (array_key_exists($match, $map) === false) {
                    $name = $this->makeStringReplacementName($index);
                    $map[$match] = $name;
                    $this->_values[$name] = str_replace("\'", "'", $matches[1][$i]);
                    $this->_literal[$name] = $match;
                    $index++;
                }
            }

            $string = strtr($string, $map);
        }

        if (str_contains($string, "'")) {
            throw new ParseErrorException("Unexpected single quote");
        }

        return $string;
    }

    public function replaceBack(string $string, $raw = false): string
    {
        $map = $raw ? $this->_literal : $this->_values;

        return strtr($string, $map);
    }

    public function clear()
    {
        $this->_values = [];
        $this->_literal = [];
    }

    public function makeStringReplacementName($index)
    {
        return $this->config->leftHash
            . $this->config->leftAdditional
            . $index
            . $this->config->rightAdditional
            . $this->config->rightHash;
    }
}

<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\DTO\StringExtractorConfig;
use App\Backticks\Syntax\Entity\PositionEntity;
use App\Backticks\Syntax\Entity\StringEntity;
use App\Backticks\Syntax\Exceptions\ParseErrorException;

class StringExtractor
{
    public const PREG_STRINGS = "/'(.*?(?<!\\\\)(\\\\\\\\)*)'/s";

    protected array $_values = [];
    protected array $_literal = [];
    protected array $_entities = [];

    public function __construct(
        protected ?StringExtractorConfig $config = null,
        protected ?LineParser $lineParser = null,
        protected ?PositionManager $positionManager = null,
    ) {
        if (null === $this->config)
        {
            $this->config = new StringExtractorConfig();
        }
    }

    public function setLineParser(?LineParser $lineParser = null)
    {
        $this->lineParser = $lineParser;
    }

    public function setPositionManager(PositionManager $positionManager)
    {
        $this->positionManager = $positionManager;
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

        if (is_array($matches) && is_array($matches[0])) {

            foreach($matches[0] as $i => $match) {
                $name = $this->makeStringReplacementName($i + 1);
                $value = str_replace("\'", "'", $matches[1][$i]);
                $this->_values[$name] = $value;
                $this->_literal[$name] = $match;

                $pos = strpos($string, $match);
                $len = strlen($match);
                $entity = new StringEntity(
                    $match,
                    $value,
                    $name,
                    $this->_position($string, $match, $name),
                );

                $this->_entities[] = $entity;

                $string = substr_replace($string, $name, $pos, $len);
            }
        }

        if (str_contains($string, "'")) {
            $pos = $this->_pos($string, "'");
            throw new ParseErrorException("Unexpected single quote", $pos);
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

    public function getEntities(): array
    {
        return $this->_entities;
    }

    protected function _pos(string $string, string $match): ?int
    {
        return $this->positionManager?->_pos($string, $match);
    }

    protected function _position(string $string, string $match, string $name): ?PositionEntity
    {
        if (null === $this->positionManager) {
            return null;
        }
        $pos = strpos($string, $match);
        $realPos = $this->positionManager->_pos($string, $match);
        $len = strlen($match);
        $replacedLen = strlen($name);

        $position = new PositionEntity(
            $name,
            $realPos,
            $len,
            $pos,
            $replacedLen,
            $this->lineParser?->getLine($realPos),
        );
        $this->positionManager->add($position);

        return $position;
    }
}

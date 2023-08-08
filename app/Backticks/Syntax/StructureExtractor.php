<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Entity\StructureEntity;
use App\Backticks\Syntax\Exceptions\ParseErrorException;
use App\Backticks\Syntax\DTO\StructureExtractorConfig;
use Psy\Util\Str;

class StructureExtractor
{
    public const PREG_STRUCTURE = "/`~([^`]*)~`/s";
    /**
     * @error can't have ~ inside structures
    public const PREG_STRUCTURE = "/`~([^`~]*)~`/s";
     */

    public int $level = 0;

    protected array $_replaced = [];

    /**
     * @var array<StructureEntity>
     */
    protected array $_entities = [];

    protected int $foundMatchesCount = 0;

    protected int $currentDelta = 0;

    public function __construct(
        protected ?StructureExtractorConfig $config = null,
        protected ?StringExtractor $stringExtractor = null,
        protected ?LineParser $lineParser = null,
    ){
        if (null === $this->config) {
            $this->config = new StructureExtractorConfig();
        }
    }

    public function setLineParser(?LineParser $lineParser = null)
    {
        $this->lineParser = $lineParser;
    }

    public function setStringExtractor(?StringExtractor $stringExtractor)
    {
        $this->stringExtractor = $stringExtractor;
    }

    public function extractStructures($string, $level = null): string
    {
        $iteration = $this->level = 0;
        $i = 1;
        $matches = $this->matchStructures($string);
        while($matches !== null && count($matches) && is_array($matches[0]) && count($matches[0]))
        {
            $iteration++;
            $this->foundMatchesCount += count($matches[0]);
            foreach($matches[0] as $k => $match)
            {
                $value = $matches[1][$k];
                $name = $this->makeStructureName($i);
                $this->_replaced[$name] = $match;

                $pos = strpos($string, $match);
                $len = strlen($match);
                $realLen = $this->_strlen($match);
                $realPos = $this->_pos($string, $match);
                $replacedLen = strlen($name);
                $entity = new StructureEntity(
                    $match,
                    $value,
                    $name,
                    $realPos,
                    $realLen,
                    $pos,
                    $replacedLen,
                    $this->lineParser?->getLine($realPos),
                );
                $this->_entities[] = $entity;
                $string = substr_replace($string, $name, $pos, $len);
                $i++;
            }

            $this->level = $iteration;
            if (null !== $level && $iteration >= $level) {
                break;
            }
            $matches = $this->matchStructures($string);
        }

        if (null === $level && (str_contains($string, '`~') || str_contains($string, '~`'))) {
            $pos = null;
            if (str_contains($string, '`~')) {
                $pos = $this->_pos($string, '`~');
            }
            if (str_contains($string, '~`')) {
                $pos = $this->_pos($string, '~`');
            }
            throw new ParseErrorException("Could not parse, check opening & closing tags: '$string'", $pos);
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

    /**
     * @error 1 test fails because it fails to replace back duplicates
    public function replaceBack($result)
    {
    $replaced = $this->_replaced;
    while (count($replaced) > 0) {
    $unreplaced = [];
    foreach($replaced as $name => $value) {
    if (str_contains($result, $name)) {
    $result = str_replace($name, $value, $result);
    } else {
    $unreplaced[$name] = $value;
    }
    }
    $replaced = $unreplaced;
    }

    return $result;
    }
     */

    public function clear()
    {
        $this->_replaced = [];
        $this->_entities = [];
        $this->currentDelta = 0;
    }

    public function makeStructureName($index)
    {
        return $this->config->leftHash
            . $this->config->leftAdditional
            . $index
            . $this->config->rightAdditional
            . $this->config->rightHash;
    }

    public function matchStructures(string $string): ?array
    {
        preg_match_all(self::PREG_STRUCTURE, $string, $matches);

        return $matches;
    }


    public function setConfig(StructureExtractorConfig $config): void
    {
        $this->config = $config;
    }

    public function getFoundMatchesCount(): int
    {
        return $this->foundMatchesCount;
    }

    public function getEntities($sort = false): array
    {
        if (false === $sort) {
            return $this->_entities;
        }

        $entities = $this->_entities;
        usort($entities, function(StructureEntity $a, StructureEntity $b){
            return $a->originalPosition - $b->originalPosition;
        });

        return $entities;
    }

    protected function _strlen(string $match): int
    {
        $len = strlen($match);

        foreach($this->_entities as $entity) {
            if (str_contains($match, $entity->name)) {
                $len -= $entity->delta;
            }
        }

        /* @error remove this to reproduce 'missing string delta' error */
        if (null !== $this->stringExtractor) {
            foreach($this->stringExtractor->getEntities() as $entity) {
                if (str_contains($match, $entity->name)) {
                    $len -= $entity->delta;
                }
            }
        }

        return $len;
    }

    protected function _pos(string $string, string $match): int
    {
        $pos = strpos($string, $match);

        return $this->getRealPos($pos, $string);
    }

    public function getRealPos(int $pos, string $string): int
    {
        $realPos = $pos;
        if (null !== $this->stringExtractor) {
            $realPos = $this->stringExtractor->getRealPos($pos, $string);
        }
        $left = substr($string, 0, $pos);
        foreach($this->_entities as $entity) {
            if (str_contains($left, $entity->name)) {
                $realPos -= $entity->delta;
            }
        }

        return $realPos;
    }

}

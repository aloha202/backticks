<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\Entity\PositionEntity;
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
        protected ?PositionManager $positionManager = null,
    ){
        if (null === $this->config) {
            $this->config = new StructureExtractorConfig();
        }
    }

    public function setPositionManager(PositionManager $positionManager)
    {
        $this->positionManager = $positionManager;
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
                $entity = new StructureEntity(
                    $match,
                    $value,
                    $name,
                    $this->_position($string, $match, $name),
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
        return $this->matchStructuresNew($string);
        /*
        preg_match_all(self::PREG_STRUCTURE, $string, $matches);

        return $matches;
        */
    }

    public function matchStructuresNew(string $string): ?array
    {
        $lookback = true;
        $recording = false;
        $substr = '';

        $found = [];

        for($i = 0; $i < strlen($string); $i++) {
            $char = $string[$i];
            $prev = $i && $lookback ? $string[$i - 1] : '';

            $seq = $prev . $char;
            if ($seq === '`~') {
                $recording = true;
                $substr = '';
                $lookback = false;
                continue;
            }

            if ($seq === '~`') {
                if ($recording) {
                    $found[] = substr_replace($substr, '', strlen($substr) - 1);
                    $substr = '';
                    $recording = false;
                }
                $lookback = false;
                continue;
            }

            if ($recording) {
                $substr .= $char;
            }

            $lookback = true;
        }

        $matches = [];
        $matches[0] = [];
        $matches[1] = [];

        foreach($found as $i => $value) {
            $matches[0][$i] = '`~' . $value .'~`';
            $matches[1][$i] = $value;
        }

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

    /**
     * @param false $sort
     * @return StructureEntity[]
     */
    public function getEntities($sort = false): array
    {
        if (false === $sort) {
            return $this->_entities;
        }

        $entities = $this->_entities;
        usort($entities, function(StructureEntity $a, StructureEntity $b){
            return ($a->positionEntity?->originalPosition ?? 0) - ($b->positionEntity?->originalPosition ?? 0);
        });

        return $entities;
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
        $len = $this->positionManager->_strlen($match);
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

<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\DTO\SubstructureExtractorConfig;
use App\Backticks\Syntax\Entity\PositionEntity;
use App\Backticks\Syntax\Entity\SubstructureEntity;

class SubstructureExtractor
{
    public const EREG = '/`([^`]*)`/';

    protected array $_entities = [];

    protected array $_prepared_entities = [];

    public function __construct(
        protected ?SubstructureExtractorConfig $config = null,
        protected ?PositionManager $positionManager = null,
    ) {
        if (null === $this->config) {
            $this->config = new SubstructureExtractorConfig();
        }
    }

    public function setPositionManager(PositionManager $positionManager)
    {
        $this->positionManager = $positionManager;
    }

    public function prepare(string $string): string
    {
        $matches = $this->extractMatches($string);

        $this->_prepared_entities = [];

        if (is_array($matches) && is_array($matches[0])) {

            foreach($matches[0] as $i => $match) {
                $name = $this->makeSubstructureName(count($this->_entities) + 1);
                $value = $matches[1][$i];

                $pos = strpos($string, $match);
                $len = strlen($match);
                $entity = new SubstructureEntity(
                    $match,
                    $value,
                    $name,
                    $this->_position($string, $match, $name),
                );

                $this->_entities[] = $entity;
                $this->_prepared_entities[] = $entity;

                $string = substr_replace($string, $name, $pos, $len);
            }
        }

        return $string;
    }

    public function extractMatches(string $string): ?array
    {
        preg_match_all(self::EREG, $string, $matches);

        return $matches;
    }

    public function makeSubstructureName($index)
    {
        return $this->config->leftHash
            . $this->config->leftAdditional
            . $index
            . $this->config->rightAdditional
            . $this->config->rightHash;
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
        );
        $this->positionManager->add($position);

        return $position;
    }

    public function getEntities(): array
    {
        return $this->_entities;
    }

    /**
     * @return array<SubstructureEntity>
     */
    public function getPreparedEntities(): array
    {
        return $this->_prepared_entities;
    }
}

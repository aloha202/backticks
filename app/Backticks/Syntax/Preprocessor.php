<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\DTO\StringExtractorConfig;
use App\Backticks\Syntax\DTO\StructureExtractorConfig;
use App\Backticks\Syntax\Entity\StructureEntity;
use App\Backticks\Syntax\Entity\SyntaxEntity;

class Preprocessor
{
    public function __construct(
        protected StringExtractor $stringExtractor,
        protected StructureExtractor $structureExtractor,
        protected LineParser $lineParser,
        protected PositionManager $positionManager,
        protected ?StructureParser $structureParser = null,
        protected ?OperatorExtractor $operatorExtractor = null,
        protected ?ConditionalParser $conditionalParser = null,
    ) {
        $this->stringExtractor->setLineParser($this->lineParser);
        $this->structureExtractor->setLineParser($this->lineParser);
        $this->structureExtractor->setPositionManager($this->positionManager);
        $this->stringExtractor->setPositionManager($this->positionManager);

        if (null === $this->operatorExtractor) {
            $this->operatorExtractor = new OperatorExtractor(null, $this->positionManager);
            $this->structureExtractor->setOperatorExtractor($this->operatorExtractor);
        }

        if (null === $this->conditionalParser) {
            $this->conditionalParser = new ConditionalParser(
                $this->operatorExtractor,
                null,
                $this->positionManager,
                $this->structureParser?->getCommandParser(),
            );
        }
    }

    public function prepare(string $string): string
    {
        $this->lineParser->parse($string);
        $string = $this->stringExtractor->extractStrings($string);
        $string = $this->operatorExtractor->extractOperators($string);
        $string = $this->structureExtractor->extractStructures($string);

        return $string;
    }

    public function parse()
    {
        if (null === $this->structureParser) {
            throw new \Exception('Structure parse is undefined');
        }

        foreach($this->getStructureEntities(true) as $structureEntity) {
            $this->structureParser->parse($structureEntity);

            foreach($structureEntity->_substructures as $substructure) {
                if ($substructure->_command->isConditional() === false) {
                    $this->structureParser->parseSingleCommand($substructure->_command);
                } else {
                    $this->conditionalParser->parse($substructure->_command);
                }
            }
        }
    }

    public function replaceBack(string $string, $raw = false): string
    {
        $string = $this->structureExtractor->replaceBack($string);
        $string = $this->stringExtractor->replaceBack($string, $raw);

        return $string;
    }


    public function setStringExtractorConfig(StringExtractorConfig $config): self
    {
        $this->stringExtractor->setConfig($config);

        return $this;
    }

    public function setStructureExtractorConfig(StructureExtractorConfig $config): self
    {
        $this->structureExtractor->setConfig($config);

        return $this;
    }

    public function getFoundStructuresCount(): int
    {
        return $this->structureExtractor->getFoundMatchesCount();
    }

    public function getAllEntities($sort = false): array
    {
        $entities1 = $this->stringExtractor->getEntities();
        $entities2 = $this->structureExtractor->getEntities();
        $entities = array_merge($entities1, $entities2);

        if ($sort) {
            usort($entities, function (SyntaxEntity $a, SyntaxEntity $b) {
                return ($a->positionEntity?->originalPosition ?? 0) - ($b->positionEntity?->originalPosition ?? 0);
            });
        }

        return $entities;
    }

    /**
     * @param false $sort
     * @return Entity\StructureEntity[]
     */
    public function getStructureEntities($sort = false)
    {
        return $this->structureExtractor->getEntities($sort);
    }


    public function getPositionManager(): PositionManager
    {
        return $this->positionManager;
    }

    public function getLineParser(): LineParser
    {
        return $this->lineParser;
    }

    public function getLine(int $pos): ?int
    {
        return $this->lineParser->getLine($pos);
    }

    public function getLineAndPositionInLine(int $pos): ?array
    {
        return $this->lineParser->getLineAndPositionInLine($pos);
    }

    public function clear()
    {
        $this->structureExtractor->clear();
        $this->stringExtractor->clear();
        $this->positionManager->clear();
    }

    public function prepareCommands()
    {
        $structures = $this->getStructureEntities(true);

        foreach($structures as $structureEntity) {
            $this->structureParser->parse($structureEntity);
        }

        return $this->structureParser->_commands;
    }

    public function _prepareStructure(StructureEntity $entity)
    {
        $this->structureExtractor->_prepare($entity);
    }

    public function setStructureParser(StructureParser $structureParser)
    {
        $this->structureParser = $structureParser;
    }

    public function getOperatorEntities($sort = true): ?array
    {
        return $this->operatorExtractor?->getEntities($sort);
    }
}

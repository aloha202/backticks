<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\DTO\StringExtractorConfig;
use App\Backticks\Syntax\DTO\StructureExtractorConfig;
use App\Backticks\Syntax\Entity\SyntaxEntity;

class Preprocessor
{
    public function __construct(
        protected StringExtractor $stringExtractor,
        protected StructureExtractor $structureExtractor,
        protected LineParser $lineParser,
    ) {
        $this->structureExtractor->setStringExtractor($this->stringExtractor);
        $this->stringExtractor->setLineParser($this->lineParser);
        $this->structureExtractor->setLineParser($this->lineParser);
    }

    public function prepare(string $string): string
    {
        $this->lineParser->parse($string);
        $string = $this->stringExtractor->extractStrings($string);
        $string = $this->structureExtractor->extractStructures($string);

        return $string;
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
                return $a->originalPosition - $b->originalPosition;
            });
        }

        return $entities;
    }
}

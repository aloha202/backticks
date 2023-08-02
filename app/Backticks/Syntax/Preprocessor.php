<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\DTO\StringExtractorConfig;
use App\Backticks\Syntax\DTO\StructureExtractorConfig;

class Preprocessor
{
    public function __construct(
        protected StringExtractor $stringExtractor,
        protected StructureExtractor $structureExtractor,
    ) {}

    public function prepare(string $string): string
    {
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
}

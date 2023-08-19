<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\DTO\OperatorExtractorConfig;
use App\Backticks\Syntax\Entity\Operator;
use App\Backticks\Syntax\Entity\PositionEntity;

class OperatorExtractor
{
    public static $_operators = [
        '~<~' => 'lt',
        '~<=~' => 'lte',
        '~>~' => 'gt',
        '~>=~' => 'gte',
        '~=~' => 'eq',
        '~<>~' => 'ne',
        '~!=~' => 'ne',
        '~||~' => 'or',

        '<=' => 'lte',
        '>=' => 'gte',
        '<>' => 'ne',
        '!=' => 'ne',
        '||' => 'or',
        '~and~' => 'and',
        '&&' => 'and',
        '~or~' => 'or',
        '~ne~' => 'ne',
        '~eq~' => 'eq',
        '~lte~' => 'lte',
        '~lt~' => 'lt',
        '~gte~' => 'gte',
        '~gt~' => 'gt',
        '<' => 'lt',
        '>' => 'gt',
        '=' => 'eq',
    ];

    /**
     * @var array<Operator>
     */
    protected array $_entities = [];

    public function __construct(
        protected ?OperatorExtractorConfig $config = null,
        protected ?PositionManager $positionManager = null,
    )
    {
        if (null === $this->config) {
            $this->config = new OperatorExtractorConfig();
        }
    }

    public function setPositionManager(PositionManager $positionManager)
    {
        $this->positionManager = $positionManager;
    }

    public function extractOperators(string $string): string
    {
        $index = 1;
        foreach (self::$_operators as $key => $value) {

            while (str_contains($string, $key)) {
                $pos = strpos($string, $key);
                $len = strlen($key);
                $name = $this->makeReplacementName($index);
                $operator = new Operator(
                    $key,
                    $value,
                    $name,
                    $this->_position($string, $key, $name),
                );

                $this->_entities[] = $operator;

                $string = substr_replace($string, $name, $pos, $len);

                $index++;
            }

        }

        return $string;
    }

    public function makeReplacementName($index)
    {
        return $this->config->leftHash
            . $this->config->leftAdditional
            . $index
            . $this->config->rightAdditional
            . $this->config->rightHash;
    }

    public function getEntities($sort = true)
    {
        $entities = $this->_entities;

        if ($sort) {
            usort($entities, function (Operator $a, Operator $b) {
                return $a->getPos() - $b->getPos();
            });
        }

        return $entities;
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
        );
        $this->positionManager->add($position);

        return $position;
    }

    public function isConditional(string $string)
    {
        foreach($this->_entities as $operator)
        {
            if ($operator->isConditional() && str_contains($string, $operator->name)) {
                return true;
            }
        }

        return false;
    }

    public function getOperatorsFromString(string $string)
    {
        return array_filter($this->_entities, function (Operator $operator) use ($string) {
            return str_contains($string, $operator->name);
        });
    }

}

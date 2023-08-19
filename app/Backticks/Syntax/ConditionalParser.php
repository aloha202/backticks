<?php

namespace App\Backticks\Syntax;

use App\Backticks\Syntax\DTO\ConditionalParserConfig;
use App\Backticks\Syntax\Entity\Command;
use App\Backticks\Syntax\Entity\Conditional;
use App\Backticks\Syntax\Entity\ConditionalGroupEntity;
use App\Backticks\Syntax\Entity\Operator;
use App\Backticks\Syntax\Entity\PositionEntity;

class ConditionalParser
{
    /**
     * @var array<ConditionalGroupEntity>
     */
    protected array $_entities = [];

    public function __construct(
        protected OperatorExtractor $operatorExtractor,
        protected ?ConditionalParserConfig $config = null,
        protected ?PositionManager $positionManager = null,
        protected ?CommandParser $commandParser = null,
    ) {
        if (null === $this->config) {
            $this->config = new ConditionalParserConfig();
        }
    }
    public function parse(Conditional $conditional): Conditional
    {
        $conditional = $this->replaceGroups($conditional);
        $this->prepareGroup($conditional->rootGroup, $conditional);
        $this->clear();

        return $conditional;
    }

    public function setPositionManager(PositionManager $positionManager)
    {
        $this->positionManager = $positionManager;
    }

    public function setCommandParser(CommandParser $commandParser) {
        $this->commandParser = $commandParser;
    }

    public function setConfig(ConditionalParserConfig $config)
    {
        $this->config = $config;
    }

    protected function prepareGroup(ConditionalGroupEntity $group, Conditional $conditional): ConditionalGroupEntity
    {
        $parts = $this->splitGroup($group->value);

        $string = $group->value;

        $group->_parts = array_map(function ($item) use ($conditional, $group, &$string) {
            if ($item instanceof Operator) {
                $conditional->_operators[] = $item;
                $group->_operators[] = $item;
                return $item;
            } else {
                if ($subGroup = $conditional->getGroupByName((trim($item)))) {
                    return $this->prepareGroup($subGroup, $conditional);
                }

                $command = new Command($item, $this->_positionCommand($string, $item, $item));
                $command->groupEntity = $group;
                $group->_commands[] = $command;
                $conditional->_commands[] = $command;
                return $command;
            }
        }, $parts);

        return $group;
    }

    public function replaceGroups(Conditional $conditional): Conditional
    {
        $string = $conditional->value;
        $matches = $this->matchRoundBraces($string);
        $i = 1;
        while (is_array($matches) && count($matches) && count($matches[0])) {
            foreach ($matches[0] as $index => $match) {
                $pos = strpos($string, $match);
                $len = strlen($match);
                $name = $this->makeGroupName($i);
                $value = $matches[1][$index];
                $groupEntity = new ConditionalGroupEntity(
                    $match,
                    $value,
                    $name,
                    $this->_position($string, $match, $name)
                );
                $groupEntity->conditional = $conditional;

                $this->_entities[] = $groupEntity;
                $conditional->_groups[] = $groupEntity;

                $string = substr_replace($string, $name, $pos, $len);

                $i++;
            }
            $matches = $this->matchRoundBraces($string);
        }

        $conditional->replacedValue = $string;

        $rootGroup = new ConditionalGroupEntity(
            $string,
            $string,
            $string,
            $this->_position($string, $string, $string)
        );

        $conditional->rootGroup = $rootGroup;

        return $conditional;
    }

    public function matchRoundBraces(string $string): ?array
    {
        $pattern = '/\(([^()]*)\)/';
        preg_match_all($pattern, $string, $matches);

        return $matches;
    }

    public function splitGroup(string $string): array
    {

        $operators = $this->operatorExtractor->getOperatorsFromString($string);
        if (count($operators) === 0) {
            return [$string];
        }

        $pregDelim = '/(' . implode('|', array_map(function (Operator $operator) {
            return preg_quote($operator->name, '/');
        }, $operators) ) . ')/';

        $split = preg_split($pregDelim, $string, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        return array_map(function (string $item) use ($operators){
            foreach ($operators as $operator) {
                if ($item === $operator->name) {
                    return $operator;
                }
            }

            return $item;
        }, $split);

    }

    protected function _position(string $string, string $match, string $name, $add = true): ?PositionEntity
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
        if ($add) {
            $this->positionManager->add($position);
        }

        return $position;
    }

    protected function _positionCommand(string &$string, string $match, string $name): ?PositionEntity
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

        $realLen = strlen($match);
        $string = substr_replace($string, str_pad('', $realLen, '`'), $pos, $realLen);

        return $position;
    }

    public function setOperatorExtractor(OperatorExtractor $extractor)
    {
        $this->operatorExtractor = $extractor;
    }

    public function makeGroupName($index)
    {
        return $this->config->leftHash
            . $this->config->leftAdditional
            . $index
            . $this->config->rightAdditional
            . $this->config->rightHash;
    }

    public function clear()
    {
        $this->_entities = [];
    }
}

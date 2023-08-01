<?php

namespace App\Libraries\QueryParser\Evaluator;

use App\Libraries\QueryParser;
use App\Libraries\QueryParser\Conditional;
use App\Libraries\QueryParser\Evaluator\Conditional\Exception\MalformedConditionalException;
use App\Libraries\QueryParser\Evaluator\Conditional\Exception\MissingOperatorException;
use App\Libraries\QueryParser\Evaluator\Conditional\Exception\UnexpectedCharacterException;
use App\Libraries\QueryParser\Evaluator\Conditional\Left;
use App\Libraries\QueryParser\Evaluator\Conditional\Right;
use App\Libraries\QueryParser\Exception\ConditionalSyntaxErrorException;
use App\Libraries\QueryParser\Preparator\Operator;
use App\Libraries\QueryParser\Preparator\OperatorExtractor;
use App\Libraries\QueryParser\Processor\ConditionalProcessor;
use App\Libraries\QueryParser\Processor\Exception\InvalidConditionalOperatorException;

class ConditionalEvaluator extends AbstractEvaluator
{
    public function __construct(
        protected QueryParser $queryParser,
        protected ConditionalProcessor $conditionalProcessor,
    )
    {
    }

    protected $value;

    protected $_operators = [
        "`<`", "`>`", "`==`", "`!=`", "`<>`", "`<=`", "`>=`", "`and`", "`or`",
        "`AND`", "`OR`"
    ];

    protected $_regSplit = "/(%s)/";
    public function evaluate(Conditional $conditional): string
    {
        $parts = $this->extractParts($conditional->value);

        $parts = $this->evaluateParts($parts);

        return $this->evalConditional($parts);
    }

    /**
     * @param array<Left|Right> $parts
     * @return array<Left|Right>
     */
    public function evaluateParts(array $parts): array
    {
        return array_map(function ($part) {
            if ($part instanceof Left) {
                $part->evaluated = $this->evaluateLeft($part);
            }
            if ($part instanceof Right) {
                $part->evaluated = $this->evaluateRight($part);
            }

            return $part;
        }, $parts);
    }

    /**
     * @param array<Left|Right> $parts
     */
    public function evalConditional(array $parts)
    {
        $parameters = $this->prepareConditional($parts);

        return $this->conditionalProcessor->process($parameters);
    }

    /**
     * @param array<Left|Right> $parts
     * @return array
     */
    public function prepareConditional(array $parts): array
    {
        $parameters = [];

        $previous = null;
        foreach($parts as $i => $part) {
            if ($part instanceof Left) {
                if ($i) {
                    $parameters[] = $this->makeElseif();
                }

                foreach($part->evaluated as $item) {
                    if ($this->isOperator($item)) {
                        $parameters[] = $this->makeOperator($item);
                    } else {
                        $parameters[] = $item;
                    }
                }
            }
            if ($part instanceof Right) {
                if ($previous !== null) {
                    if ($previous instanceof Left) {
                        $parameters[] = $this->makeThen();
                    } else if ($previous instanceof Right) {
                        $parameters[] = $this->makeElse();
                    }
                }

                $parameters[] = $part->evaluated;
            }

            $previous = $part;
        }

        return $parameters;
    }

    public function evaluateLeft(Left $left)
    {
        $chunks = $this->splitLeft($left);

        return array_map(function ($chunk) {
            return $this->isOperator($chunk) ? $chunk : $this->evaluateQuery($chunk);
        }, $chunks);
    }

    public function evaluateRight(Right $right)
    {
        $value = $right->getRealValue();

        return $this->queryParser !== null ? $this->queryParser->parse($value , false) : $value;
    }

    public function isOperator(string $chunk) {
        return in_array($chunk, $this->_operators);
    }

    public function splitLeft(Left $left): array {
        $value = $left->getRealValue();

        $ereg = sprintf($this->_regSplit, join('|', $this->_operators));
        $chunks = preg_split($ereg, $value, -1, PREG_SPLIT_DELIM_CAPTURE);

        return array_map(
            function ($item){
                return trim($item);
            },
            $chunks
        );
    }

    public function evaluateQuery(string $query): string
    {
        return null !== $this->queryParser ? $this->queryParser->parse($query, false) : $query;
    }


    /**
     * @param string $conditionalExpression
     * @return array<Left|Right>
     * @throws MissingOperatorException
     * @throws UnexpectedCharacterException
     */
    public function extractParts(string $conditionalExpression): array
    {
        $this->value = $conditionalExpression;
        $parts = [];
        $expecting = ['('];
        $recorded = '';
        $expectingOperator = false;
        $ignore_chars = [" ", "\n", "\t", "\r"];
        $expectingElseOrElseif = ['(', '{'];
        $allowed_chars = array_merge($ignore_chars, $expecting);
        $elseIsFound = false;
        for($i = 0; $i < strlen($conditionalExpression); $i++) {
            $char = $conditionalExpression[$i];
            if (is_array($allowed_chars) && in_array($char, $allowed_chars) === false) {
                throw new UnexpectedCharacterException("Unexpected character '$char'");
            }


            if (in_array($char, $expecting)) {
                if ($char === '(') {
                    $allowed_chars = false;
                    if ($expectingOperator) {
                        if (strtolower(trim($recorded)) !== 'elseif') {
                            throw new MissingOperatorException("Missing operator 'elseif'");
                        }
                    }
                    $expectingOperator = false;
                    $expecting = [')'];
                    $recorded = '(';
                    continue;
                }

                if ($char === ')') {
                    $expecting = ['{'];
                    $allowed_chars = array_merge($ignore_chars, $expecting);
                    $parts[] = new Left($recorded . ')');
                    continue;
                }

                if ($char === '{') {
                    $allowed_chars = false;
                    if ($expectingOperator) {
                        if (strtolower(trim($recorded)) !== 'else') {
                            throw new MissingOperatorException("Missing operator 'else'");
                        }
                        $elseIsFound = true;
                    }
                    $expectingOperator = false;
                    $expecting = ['}'];
                    $recorded = '{';
                    continue;
                }

                if ($char === '}') {
                    $allowed_chars = false;
                    if ($elseIsFound) {
                        $allowed_chars = $ignore_chars;
                    }
                    $expecting = $expectingElseOrElseif;
                    $expectingOperator = true;
                    $parts[] = new Right($recorded . '}');
                    $recorded = '';
                    continue;
                }

            }

            $recorded .= $char;
        }

        if ($expecting[0] === ')') {
            throw new MalformedConditionalException(sprintf("Invalid conditional '%s': missing ')'", $this->value));
        }

        if ($expecting[0] === '}') {
            throw new MalformedConditionalException(sprintf("Invalid conditional '%s': missing '}'", $this->value));
        }

        if ($expecting === $expectingElseOrElseif) {
            if (trim($recorded) !== '') {
                $part = strtolower(trim($recorded));
                if (in_array($part, ['else', 'elseif'])) {
                    throw new MalformedConditionalException(sprintf(
                        "Missing '%s' after '%s' operator",
                        $part === 'else' ? '{' : '(',
                        $part
                    ));
                } else {
                    throw new UnexpectedCharacterException(sprintf("Unexpected character '%s'", $part[0]));
                }
            }
        }

        if (count($parts) === 0) {
            if ($expecting[0] === '(') {
                throw new MalformedConditionalException(sprintf("Invalid conditional '%s': missing '('", $this->value));
            }
        }
        if (count($parts) === 1 || $parts[count($parts) - 1] instanceof Left) {
            throw new MalformedConditionalException(sprintf("Invalid conditional '%s': missing right part", $this->value));
        }



        return $parts;
    }

    public function makeOperator(string $backticked): Operator
    {
        $operator = OperatorExtractor::getBacktickedOperatorValue(strtolower($backticked));
        if (null === $operator) {
            throw new InvalidConditionalOperatorException("Invalid conditional operator: $backticked");
        }
        return new Operator($operator, $operator, $operator);
    }

    public function makeThen()
    {
        return new Operator('then', 'then', 'then');
    }

    public function makeElse()
    {
        return new Operator('else', 'else', 'else');
    }

    public function makeElseif()
    {
        return new Operator('elseif', 'elseif', 'elseif');
    }
}

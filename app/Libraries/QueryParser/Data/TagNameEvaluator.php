<?php

namespace App\Libraries\QueryParser\Data;

use App\Libraries\QueryParser\Evaluator\Tag\Name as TagName;
use App\Libraries\QueryParser\Exception\NoMethodImplementedForTagNameException;
use App\Libraries\QueryParser\Exception\TagRepositoryException;
use App\Libraries\QueryParser\Exception\UnknownTagNameException;


class TagNameEvaluator
{
    protected $repository;
    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param TagName $tagName
     * @return mixed
     * @throws TagRepositoryException
     */
    public function evaluate(TagName $tagName)
    {
        try {
            return $this->repository->getTag($tagName->value);
        } catch (\Throwable $e) {
            throw new TagRepositoryException($e->getMessage());
        }
    }

}

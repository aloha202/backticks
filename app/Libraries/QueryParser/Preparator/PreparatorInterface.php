<?php

namespace App\Libraries\QueryParser\Preparator;

interface PreparatorInterface
{
    /**
     * @param string|array $query
     * @param bool $raw
     * @return string|array
     */
    public function replaceBack($query, bool $raw = false);

    /**
     * @param string $query
     * @return string
     */
    public function prepare(string $query): string;
}

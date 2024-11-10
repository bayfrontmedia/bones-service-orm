<?php

namespace Bayfront\BonesService\Orm\Interfaces;

interface QueryParserInterface
{

    /**
     * Get fields to be returned in dot notation.
     *
     * See: https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#select
     *
     * @return array
     */
    public function getFields(): array;

    /**
     * Get fields to filter by.
     *
     * See: https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#where
     *
     * @return array
     */
    public function getFilter(): array;

    /**
     * Get case-insensitive search string.
     *
     * @return string
     */
    public function getSearch(): string;

    /**
     * Get fields to sort/order by.
     *
     * See: https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#orderby
     *
     * Values without a prefix or prefixed with a + will be ordered ascending.
     * Values prefixed with a - will be ordered descending.
     *
     * @return array
     */
    public function getSort(): array;

    /**
     * Get fields to group by.
     *
     * See: https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#groupby
     *
     * @return array
     */
    public function getGroup(): array;

    /**
     * Get limit.
     *
     * See: https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#limit
     *
     * @return null|int (-1 for maximum allowed)
     */
    public function getLimit(): ?int;

    /**
     * Get pagination method.
     *
     * @return string
     */
    public function getPaginationMethod(): string;

    /**
     * Get page.
     *
     * @return int
     */
    public function getPage(): int;

    /**
     * Get before cursor.
     *
     * @return string
     */
    public function getBeforeCursor(): string;

    /**
     * Get after cursor.
     *
     * @return string
     */
    public function getAfterCursor(): string;

    /**
     * Get aggregate functions.
     *
     * See: https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#aggregate
     *
     * @return array
     */
    public function getAggregate(): array;

    /**
     * Get pagination.
     *
     * @return string
     */
    public function getPagination(): string;

}
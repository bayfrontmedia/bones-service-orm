<?php

namespace Bayfront\BonesService\Orm;

use Bayfront\BonesService\Orm\Exceptions\DoesNotExistException;
use Bayfront\BonesService\Orm\Exceptions\InvalidRequestException;
use Bayfront\BonesService\Orm\Interfaces\QueryParserInterface;
use Bayfront\BonesService\Orm\Models\ResourceModel;
use Bayfront\SimplePdo\Query;

/**
 * Collection of resources.
 */
class OrmCollection
{

    private ResourceModel $resourceModel;
    private Query $query;
    private QueryParserInterface $parser;
    private array $collection;
    private string $cursor_field;
    private ?int $limit;

    public function __construct(ResourceModel $resourceModel, Query $query, QueryParserInterface $parser, array $collection, string $cursor_field, int|null $limit)
    {
        $this->resourceModel = $resourceModel;
        $this->query = $query;
        $this->parser = $parser;
        $this->collection = $collection;
        $this->cursor_field = $cursor_field;
        $this->limit = $limit;
    }

    /**
     * Get total aggregate count from query.
     *
     * This method queries the database.
     *
     * @return int
     */
    private function getAggregateCount(): int
    {
        $start = microtime(true);
        $count = $this->query->aggregate($this->query::AGGREGATE_COUNT);
        $this->resourceModel->ormService->db->setQueryTime($this->resourceModel->ormService->db->getCurrentConnectionName(), microtime(true) - $start);
        return $count;
    }

    /**
     * Get model instance of collection.
     *
     * @return ResourceModel
     */
    public function getModel(): ResourceModel
    {
        return $this->resourceModel;
    }

    /**
     * Get fully namespaced class of collection.
     *
     * @return string
     */
    public function getModelClassName(): string
    {
        return $this->resourceModel::class;
    }

    /**
     * Get primary key field name of collection.
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->resourceModel->getPrimaryKey();
    }

    /**
     * Get collection count.
     *
     * @return int
     */
    public function getCount(): int
    {
        return count($this->collection);
    }

    /**
     * List collection.
     *
     * @return array
     */
    public function list(): array
    {
        return $this->collection;
    }

    /**
     * Read single resource from the collection.
     *
     * @param mixed $primary_key_id
     * @return array
     * @throws DoesNotExistException
     */
    public function read(mixed $primary_key_id): array
    {

        foreach ($this->collection as $resource) {

            if (isset($resource[$this->resourceModel->getPrimaryKey()]) && $resource[$this->resourceModel->getPrimaryKey()] === $primary_key_id) {
                return $resource;
            }

        }

        throw new DoesNotExistException('Unable to read resource from collection: Resource does not exist');

    }

    /**
     * Get collection pagination.
     *
     * This method queries the database.
     *
     * @return array
     * @throws InvalidRequestException
     */
    public function getPagination(): array
    {

        $pagination = strtolower($this->parser->getPagination());

        if ($pagination == '') {

            return [];

        } else {

            if (!in_array($pagination, [
                $this->parser::PAGINATION_PAGE,
                $this->parser::PAGINATION_CURSOR
            ])) {
                throw new InvalidRequestException('Unable to get pagination: Invalid pagination value (' . strtolower($pagination) . ')');
            }

            $results_current = $this->getCount();

            if ($pagination == $this->parser::PAGINATION_PAGE) {

                $results_total = $this->getAggregateCount();
                $page_current = $this->parser->getPage();

                if (is_int($this->limit) && $this->limit > 0) {
                    $page_total = ceil($results_total / $this->limit);
                } else { // Limit = NULL (no limit) or 0
                    $page_total = 1;
                }

                $return = [
                    'results' => [
                        'current' => $results_current,
                        'total' => $results_total,
                        'from' => null,
                        'to' => null
                    ],
                    'page' => [
                        'size' => ($this->limit !== null) ? $this->limit : $results_current,
                        'current' => $page_current,
                        'previous' => null,
                        'next' => null,
                        'total' => $page_total
                    ]
                ];

                if ($results_current > 0) {

                    if (is_int($this->limit) && $this->limit > 0) {
                        $return['results']['to'] = $this->limit * $page_current;
                    } else {
                        $return['results']['to'] = $results_total;
                    }

                    if ($results_total < $return['results']['to']) { // Last page
                        $return['results']['to'] = $results_total;
                    }

                    $return['results']['from'] = $return['results']['to'] - $results_current + 1;

                    if ($page_current > 1) {
                        $return['page']['previous'] = $page_current - 1;
                    }

                    if ($page_total > $page_current) {
                        $return['page']['next'] = $page_current + 1;
                    }

                } else if ($page_total > 0 && $page_current == 2) {
                    $return['page']['previous'] = 1;
                }

            } else { // $this->parser::PAGINATION_CURSOR

                $return = [
                    'results' => [
                        'current' => $results_current,
                    ],
                    'cursor' => [
                        'first' => null,
                        'last' => null
                    ]
                ];

                if ($results_current > 0) {

                    if (isset($this->collection[0][$this->cursor_field])) {
                        $return['cursor']['first'] = base64_encode($this->collection[0][$this->cursor_field]);
                    }

                    if (isset($this->collection[array_key_last($this->collection)][$this->cursor_field])) {
                        $return['cursor']['last'] = base64_encode($this->collection[array_key_last($this->collection)][$this->cursor_field]);
                    }

                }

            }

            return $return;

        }

    }

    /**
     * Get aggregate results from query.
     *
     * This method queries the database once for each aggregate function.
     *
     * @return array
     * @throws InvalidRequestException
     */
    public function getAggregate(): array
    {

        $return = [];

        foreach ($this->parser->getAggregate() as $clause) {

            if (!is_array($clause)) {
                throw new InvalidRequestException('Unable to get aggregate: Invalid aggregate clause');
            }

            foreach ($clause as $fx => $col) {

                $fx = strtoupper($fx);

                if (!in_array($fx, [
                    $this->query::AGGREGATE_AVG,
                    $this->query::AGGREGATE_AVG_DISTINCT,
                    $this->query::AGGREGATE_COUNT,
                    $this->query::AGGREGATE_COUNT_DISTINCT,
                    $this->query::AGGREGATE_MAX,
                    $this->query::AGGREGATE_MIN,
                    $this->query::AGGREGATE_SUM,
                    $this->query::AGGREGATE_SUM_DISTINCT
                ])) {
                    throw new InvalidRequestException('Unable to list resource: Invalid aggregate function (' . $fx . ')');
                }

                $start_time = microtime(true);
                $return[$col][$fx] = $this->query->aggregate($fx, $col);
                $this->resourceModel->ormService->db->setQueryTime($this->resourceModel->ormService->db->getCurrentConnectionName(), microtime(true) - $start_time);

            }

        }

        return $return;

    }

    /**
     * Get total number of database queries.
     *
     * @return int
     */
    public function getTotalQueries(): int
    {
        return $this->resourceModel->ormService->db->getTotalQueries();
    }

    /**
     * Get total time elapsed in seconds for all database queries.
     *
     * @param int $decimals
     * @return float
     */
    public function getQueryTime(int $decimals = 3): float
    {
        return $this->resourceModel->ormService->db->getQueryTime($decimals);
    }

}
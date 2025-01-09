<?php

namespace Bayfront\BonesService\Orm\Utilities\Parsers;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\BonesService\Orm\Exceptions\InvalidRequestException;
use Bayfront\BonesService\Orm\Interfaces\QueryParserInterface;

/**
 * Parse a query from an array.
 */
class QueryParser implements QueryParserInterface
{

    private array $query;

    /**
     * @param array $query (Array representing the $_GET superglobal variable)
     * @throws InvalidRequestException
     */
    public function __construct(array $query)
    {
        $this->query = $query;
        $this->validateQuery();
    }

    /*
     * Valid pagination methods
     */
    public const PAGINATION_PAGE = 'page';
    public const PAGINATION_CURSOR = 'cursor';
    public const PAGINATION_BEFORE = 'before';
    public const PAGINATION_AFTER = 'after';

    private ?int $limit = null;

    private string $pagination_method = self::PAGINATION_PAGE;
    private int $page = 1;
    private string $before_cursor = '';
    private string $after_cursor = '';

    /**
     * Validate query string keys.
     *
     * @return void
     * @throws InvalidRequestException
     */
    private function validateQuery(): void
    {

        /*
         * Search
         */

        if (!is_string(Arr::get($this->query, 'search', ''))) {
            throw new InvalidRequestException('Unable to parse request: Invalid search format');
        }

        /*
         * Limit
         */

        $limit = Arr::get($this->query, 'limit');

        if ($limit !== null) {

            if ((int)$limit < -1) {
                throw new InvalidRequestException('Unable to parse request: Invalid limit format');
            }

            $this->limit = (int)$limit;

        }

        /*
         * Pagination method
         */

        $pagination_type = Arr::only($this->query, [
            self::PAGINATION_PAGE,
            self::PAGINATION_BEFORE,
            self::PAGINATION_AFTER
        ]);

        if (count($pagination_type) > 1) {
            throw new InvalidRequestException('Unable to parse request: Only one pagination method is allowed');
        }

        if (Arr::has($this->query, self::PAGINATION_PAGE)) {

            $this->pagination_method = self::PAGINATION_PAGE;
            $this->page = (int)Arr::get($this->query, self::PAGINATION_PAGE, 1);

            if ($this->page < 1) {
                throw new InvalidRequestException('Unable to parse request: Invalid page format');
            }

        } else if (Arr::has($this->query, self::PAGINATION_BEFORE)) {

            $this->pagination_method = self::PAGINATION_BEFORE;
            $this->before_cursor = Arr::get($this->query, self::PAGINATION_BEFORE);

        } else if (Arr::has($this->query, self::PAGINATION_AFTER)) {

            $this->pagination_method = self::PAGINATION_AFTER;
            $this->after_cursor = Arr::get($this->query, self::PAGINATION_AFTER);

        }

        /*
         * Pagination
         */

        if (!is_string(Arr::get($this->query, 'pagination', ''))) {
            throw new InvalidRequestException('Unable to parse request: Invalid pagination format');
        }

    }

    /**
     * Explode commas if a string.
     *
     * @param mixed $value
     * @return array
     */
    private function explodeCommas(mixed $value): array
    {
        if (is_string($value)) {
            return explode(',', $value);
        }
        return (array)$value;
    }

    /**
     * Decode JSON if a string.
     *
     * @param mixed $value
     * @return array
     */
    private function decodeJson(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        } else if (is_string($value)) {
            $json = json_decode($value, true);
            if (is_array($json)) {
                return $json;
            }
        }

        return [];

    }

    /**
     * @inheritDoc
     */
    public function getFields(): array
    {
        return $this->explodeCommas(Arr::get($this->query, 'fields', []));
    }

    /**
     * @inheritDoc
     */
    public function getFilter(): array
    {
        return $this->decodeJson(Arr::get($this->query, 'filter', []));
    }

    /**
     * @inheritDoc
     */
    public function getSearch(): string
    {
        return Arr::get($this->query, 'search', '');
    }

    /**
     * @inheritDoc
     */
    public function getSort(): array
    {
        return $this->explodeCommas(Arr::get($this->query, 'sort', []));
    }

    /**
     * @inheritDoc
     */
    public function getGroup(): array
    {
        return $this->explodeCommas(Arr::get($this->query, 'group', []));
    }

    /**
     * @inheritDoc
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * @inheritDoc
     */
    public function getPaginationMethod(): string
    {
        return $this->pagination_method;
    }

    /**
     * @inheritDoc
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @inheritDoc
     */
    public function getBeforeCursor(): string
    {
        return $this->before_cursor;
    }

    /**
     * @inheritDoc
     */
    public function getAfterCursor(): string
    {
        return $this->after_cursor;
    }

    /**
     * @inheritDoc
     */
    public function getAggregate(): array
    {
        return $this->decodeJson(Arr::get($this->query, 'aggregate', []));
    }

    /**
     * @inheritDoc
     */
    public function getPagination(): string
    {
        return Arr::get($this->query, 'pagination', '');
    }

}
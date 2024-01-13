<?php
namespace Neos\Flow\Persistence;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An empty result list
 *
 * @api
 */
class EmptyQueryResult implements QueryResultInterface
{
    /**
     * @var QueryInterface
     */
    protected $query;

    /**
     * Constructor
     *
     * @param QueryInterface $query
     */
    public function __construct(QueryInterface $query)
    {
        $this->query = $query;
    }

    /**
     * Returns a clone of the query object
     *
     * @return QueryInterface
     * @api
     */
    public function getQuery(): QueryInterface
    {
        return $this->query;
    }

    /**
     * Returns NULL
     *
     * @return object Returns NULL in this case
     * @api
     */
    public function getFirst()
    {
        return null;
    }

    /**
     * Returns an empty array
     *
     * @return array
     * @api
     */
    public function toArray(): array
    {
        return [];
    }

    public function current(): mixed
    {
        return null;
    }

    public function next(): void
    {
    }

    public function key(): int
    {
        return 0;
    }

    public function valid(): bool
    {
        return false;
    }

    public function rewind(): void
    {
    }

    public function offsetExists($offset): bool
    {
        return false;
    }

    public function offsetGet($offset): mixed
    {
        return null;
    }

    /**
     * @param mixed $offset The offset is ignored in this case
     * @param mixed $value The value is ignored in this case
     */
    public function offsetSet($offset, $value): void
    {
    }

    /**
     * @param mixed $offset The offset is ignored in this case
     */
    public function offsetUnset($offset): void
    {
    }

    public function count(): int
    {
        return 0;
    }
}

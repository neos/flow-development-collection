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
 * A persistence query interface.
 *
 * The main point when implementing this is to make sure that methods with a
 * return type of "object" return something that can be fed to matching() and
 * all constraint-generating methods (like logicalAnd(), equals(), like(), ...).
 *
 * This allows for code like
 * $query->matching($query->equals('foo', 'bar'))->setLimit(10)->execute();
 *
 * @api
 */
interface QueryInterface
{
    /**
     * The '=' comparison operator.
     * @api
    */
    const OPERATOR_EQUAL_TO = 1;

    /**
     * The '!=' comparison operator.
     * @api
    */
    const OPERATOR_NOT_EQUAL_TO = 2;

    /**
     * The '<' comparison operator.
     * @api
    */
    const OPERATOR_LESS_THAN = 3;

    /**
     * The '<=' comparison operator.
     * @api
    */
    const OPERATOR_LESS_THAN_OR_EQUAL_TO = 4;

    /**
     * The '>' comparison operator.
     * @api
    */
    const OPERATOR_GREATER_THAN = 5;

    /**
     * The '>=' comparison operator.
     * @api
    */
    const OPERATOR_GREATER_THAN_OR_EQUAL_TO = 6;

    /**
     * The 'like' comparison operator.
     * @api
    */
    const OPERATOR_LIKE = 7;

    /**
     * The 'contains' comparison operator for collections.
     * @api
    */
    const OPERATOR_CONTAINS = 8;

    /**
     * The 'in' comparison operator.
     * @api
    */
    const OPERATOR_IN = 9;

    /**
     * The 'is NULL' comparison operator.
     * @api
    */
    const OPERATOR_IS_NULL = 10;

    /**
     * The 'is empty' comparison operator for collections.
     * @api
    */
    const OPERATOR_IS_EMPTY = 11;

    /**
     * Constants representing the direction when ordering result sets.
     */
    const ORDER_ASCENDING = 'ASC';
    const ORDER_DESCENDING = 'DESC';

    /**
     * Returns the type this query cares for.
     *
     * @return string
     * @api
     */
    public function getType();

    /**
     * Executes the query and returns the result.
     *
     * @param bool $cacheResult If the result cache should be used
     * @return QueryResultInterface The query result
     * @api
     */
    public function execute($cacheResult = false);

    /**
     * Returns the query result count.
     *
     * @return integer The query result count
     * @api
     */
    public function count();

    /**
     * Sets the property names to order the result by. Expected like this:
     * array(
     *  'foo' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
     *  'bar' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING
     * )
     *
     * @param array $orderings The property names to order by
     * @return QueryInterface
     * @api
     */
    public function setOrderings(array $orderings);

    /**
     * Gets the property names to order the result by, like this:
     * array(
     *  'foo' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
     *  'bar' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING
     * )
     *
     * @return array
     * @api
     */
    public function getOrderings();

    /**
     * Sets the maximum size of the result set to limit. Returns $this to allow
     * for chaining (fluid interface).
     *
     * @param integer $limit
     * @return QueryInterface
     * @api
     */
    public function setLimit($limit);

    /**
     * Returns the maximum size of the result set to limit.
     *
     * @param integer
     * @api
     */
    public function getLimit();

    /**
     * Sets the DISTINCT flag for this query.
     *
     * @param boolean $distinct
     * @return QueryInterface
     * @api
     */
    public function setDistinct($distinct = true);

    /**
     * Returns the DISTINCT flag for this query.
     *
     * @return boolean
     * @api
     */
    public function isDistinct();

    /**
     * Sets the start offset of the result set to offset. Returns $this to
     * allow for chaining (fluid interface).
     *
     * @param integer $offset
     * @return QueryInterface
     * @api
     */
    public function setOffset($offset);

    /**
     * Returns the start offset of the result set.
     *
     * @return integer
     * @api
     */
    public function getOffset();

    /**
     * The constraint used to limit the result set. Returns $this to allow
     * for chaining (fluid interface).
     *
     * @param object $constraint Some constraint, depending on the backend
     * @return QueryInterface
     * @api
     */
    public function matching($constraint);

    /**
     * Gets the constraint for this query.
     *
     * @return mixed the constraint, or null if none
     * @api
    */
    public function getConstraint();

    /**
     * Performs a logical conjunction of the two given constraints. The method
     * takes one or more constraints and concatenates them with a boolean AND.
     * It also accepts a single array of constraints to be concatenated.
     *
     * @param mixed ...$constraint1 The first of multiple constraints or an array of constraints.
     * @return object
     * @api
     */
    public function logicalAnd($constraint1);

    /**
     * Performs a logical disjunction of the two given constraints. The method
     * takes one or more constraints and concatenates them with a boolean OR.
     * It also accepts a single array of constraints to be concatenated.
     *
     * @param mixed ...$constraint1 The first of multiple constraints or an array of constraints.
     * @return object
     * @api
     */
    public function logicalOr($constraint1);

    /**
     * Performs a logical negation of the given constraint
     *
     * @param object $constraint Constraint to negate
     * @return object
     * @api
     */
    public function logicalNot($constraint);

    /**
     * Returns an equals criterion used for matching objects against a query.
     *
     * It matches if the $operand equals the value of the property named
     * $propertyName. If $operand is NULL a strict check for NULL is done. For
     * strings the comparison can be done with or without case-sensitivity.
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with
     * @param boolean $caseSensitive Whether the equality test should be done case-sensitive for strings
     * @return object
     * @todo Decide what to do about equality on multi-valued properties
     * @api
     */
    public function equals($propertyName, $operand, $caseSensitive = true);

    /**
     * Returns a like criterion used for matching objects against a query.
     * Matches if the property named $propertyName is like the $operand, using
     * standard SQL wildcards.
     *
     * @param string $propertyName The name of the property to compare against
     * @param string $operand The value to compare with
     * @param boolean $caseSensitive Whether the matching should be done case-sensitive
     * @return object
     * @throws Exception\InvalidQueryException if used on a non-string property
     * @api
     */
    public function like($propertyName, $operand, $caseSensitive = true);

    /**
     * Returns a "contains" criterion used for matching objects against a query.
     * It matches if the multivalued property contains the given operand.
     *
     * If NULL is given as $operand, there will never be a match!
     *
     * @param string $propertyName The name of the multivalued property to compare against
     * @param mixed $operand The value to compare with
     * @return object
     * @throws Exception\InvalidQueryException if used on a single-valued property
     * @api
     */
    public function contains($propertyName, $operand);

    /**
     * Returns an "isEmpty" criterion used for matching objects against a query.
     * It matches if the multivalued property contains no values or is NULL.
     *
     * @param string $propertyName The name of the multivalued property to compare against
     * @return boolean
     * @throws Exception\InvalidQueryException if used on a single-valued property
     * @api
     */
    public function isEmpty($propertyName);

    /**
     * Returns an "in" criterion used for matching objects against a query. It
     * matches if the property's value is contained in the multivalued operand.
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with, multivalued
     * @return object
     * @throws Exception\InvalidQueryException if used on a multi-valued property
     * @api
     */
    public function in($propertyName, $operand);

    /**
     * Returns a less than criterion used for matching objects against a query
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with
     * @return object
     * @throws Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
     * @api
     */
    public function lessThan($propertyName, $operand);

    /**
     * Returns a less or equal than criterion used for matching objects against a query
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with
     * @return object
     * @throws Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
     * @api
     */
    public function lessThanOrEqual($propertyName, $operand);

    /**
     * Returns a greater than criterion used for matching objects against a query
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with
     * @return object
     * @throws Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
     * @api
     */
    public function greaterThan($propertyName, $operand);

    /**
     * Returns a greater than or equal criterion used for matching objects against a query
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with
     * @return object
     * @throws Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
     * @api
     */
    public function greaterThanOrEqual($propertyName, $operand);
}

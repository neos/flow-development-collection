<?php
namespace Neos\Flow\Persistence\Generic;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\Persistence\Generic\Exception\InvalidNumberOfConstraintsException;
use Neos\Flow\Persistence\Generic\Qom\QueryObjectModelFactory;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Reflection\ClassSchema;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Utility\TypeHandling;

/**
 * The Query class used to run queries like
 * $query->matching($query->equals('foo', 'bar'))->setLimit(10)->execute();
 *
 * @api
 */
class Query implements QueryInterface
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var ClassSchema
     */
    protected $classSchema;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var QueryObjectModelFactory
     */
    protected $qomFactory;

    /**
     * @var Qom\Constraint
     */
    protected $constraint;

    /**
     * The property names to order the result by. Expected like this:
     * array(
     *  'foo' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
     *  'bar' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING
     * )
     *
     * @var array
     */
    protected $orderings = [];

    /**
     * @var integer
     */
    protected $limit;

    /**
     * @var boolean
     */
    protected $distinct = false;

    /**
     * @var integer
     */
    protected $offset = 0;

    /**
     * Constructs a query object working on the given type
     *
     * @param string $type
     * @param ReflectionService $reflectionService
     */
    public function __construct($type, ReflectionService $reflectionService)
    {
        $this->type = $type;
        $this->classSchema = $reflectionService->getClassSchema($type);
    }

    /**
     * Injects the Flow object factory
     *
     * @param ObjectManagerInterface $qomFactory
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $qomFactory)
    {
        $this->objectManager = $qomFactory;
    }

    /**
     * Injects the Flow QOM factory
     *
     * @param QueryObjectModelFactory $qomFactory
     * @return void
     */
    public function injectQomFactory(QueryObjectModelFactory $qomFactory)
    {
        $this->qomFactory = $qomFactory;
    }

    /**
     * Returns the type this query cares for.
     *
     * @return string
     * @api
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Executes the query and returns the result
     *
     * @param bool $cacheResult If the result cache should be used
     * @return \Neos\Flow\Persistence\QueryResultInterface The query result
     * @api
     */
    public function execute($cacheResult = false)
    {
        return new QueryResult($this, $cacheResult);
    }

    /**
     * Returns the query result count
     *
     * @return integer The query result count
     * @api
     */
    public function count()
    {
        $result = new QueryResult($this);
        return $result->count();
    }

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
    public function setOrderings(array $orderings)
    {
        $this->orderings = $orderings;
        return $this;
    }

    /**
     * Returns the property names to order the result by, like this:
     * array(
     *  'foo' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
     *  'bar' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING
     * )
     *
     * @return array
     * @api
     */
    public function getOrderings()
    {
        return $this->orderings;
    }

    /**
     * Sets the maximum size of the result set to limit. Returns $this to allow
     * for chaining (fluid interface)
     *
     * @param integer $limit
     * @return QueryInterface
     * @throws \InvalidArgumentException
     * @api
     */
    public function setLimit($limit)
    {
        if ($limit < 1 || !is_int($limit)) {
            throw new \InvalidArgumentException('setLimit() accepts only integers greater 0.', 1263387249);
        }
        $this->limit = $limit;

        return $this;
    }

    /**
     * Returns the maximum size of the result set to limit.
     *
     * @return integer
     * @api
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Sets the DISTINCT flag for this query.
     *
     * @param boolean $distinct
     * @return QueryInterface
     * @api
     */
    public function setDistinct($distinct = true)
    {
        $this->distinct = $distinct;
        return $this;
    }

    /**
     * Returns the DISTINCT flag for this query.
     *
     * @return boolean
     * @api
     */
    public function isDistinct()
    {
        return $this->distinct;
    }

    /**
     * Sets the start offset of the result set to $offset. Returns $this to
     * allow for chaining (fluid interface)
     *
     * @param integer $offset
     * @return QueryInterface
     * @throws \InvalidArgumentException
     * @api
     */
    public function setOffset($offset)
    {
        if ($offset < 1 || !is_int($offset)) {
            throw new \InvalidArgumentException('setOffset() accepts only integers greater 0.', 1263387252);
        }
        $this->offset = $offset;

        return $this;
    }

    /**
     * Returns the start offset of the result set.
     *
     * @return integer
     * @api
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * The constraint used to limit the result set. Returns $this to allow
     * for chaining (fluid interface)
     *
     * @param Qom\Constraint $constraint
     * @return QueryInterface
     * @api
     */
    public function matching($constraint)
    {
        $this->constraint = $constraint;
        return $this;
    }

    /**
     * Gets the constraint for this query.
     *
     * @return Qom\Constraint the constraint, or null if none
     * @api
    */
    public function getConstraint()
    {
        return $this->constraint;
    }

    /**
     * Performs a logical conjunction of the two given constraints. The method
     * takes one or more contraints and concatenates them with a boolean AND.
     * It also accepts a single array of constraints to be concatenated.
     *
     * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
     * @return Qom\LogicalAnd
     * @throws InvalidNumberOfConstraintsException
     * @api
     */
    public function logicalAnd($constraint1)
    {
        if (is_array($constraint1)) {
            $resultingConstraint = array_shift($constraint1);
            $constraints = $constraint1;
        } else {
            $constraints = func_get_args();
            $resultingConstraint = array_shift($constraints);
        }

        if ($resultingConstraint === null) {
            throw new InvalidNumberOfConstraintsException('There must be at least one constraint or a non-empty array of constraints given.', 1268056288);
        }

        foreach ($constraints as $constraint) {
            $resultingConstraint = $this->qomFactory->_and($resultingConstraint, $constraint);
        }
        return $resultingConstraint;
    }

    /**
     * Performs a logical disjunction of the two given constraints. The method
     * takes one or more constraints and concatenates them with a boolean OR.
     * It also accepts a single array of constraints to be concatenated.
     *
     * @param object $constraint1 The first of multiple constraints or an array of constraints.
     * @return Qom\LogicalOr
     * @throws InvalidNumberOfConstraintsException
     * @api
     */
    public function logicalOr($constraint1)
    {
        if (is_array($constraint1)) {
            $resultingConstraint = array_shift($constraint1);
            $constraints = $constraint1;
        } else {
            $constraints = func_get_args();
            $resultingConstraint = array_shift($constraints);
        }

        if ($resultingConstraint === null) {
            throw new InvalidNumberOfConstraintsException('There must be at least one constraint or a non-empty array of constraints given.', 1268056289);
        }

        foreach ($constraints as $constraint) {
            $resultingConstraint = $this->qomFactory->_or($resultingConstraint, $constraint);
        }
        return $resultingConstraint;
    }

    /**
     * Performs a logical negation of the given constraint
     *
     * @param object $constraint Constraint to negate
     * @return Qom\LogicalNot
     * @api
     */
    public function logicalNot($constraint)
    {
        return $this->qomFactory->not($constraint);
    }

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
    public function equals($propertyName, $operand, $caseSensitive = true)
    {
        if ($operand === null) {
            $comparison = $this->qomFactory->comparison(
                $this->qomFactory->propertyValue($propertyName, '_entity'),
                QueryInterface::OPERATOR_IS_NULL
            );
        } elseif (is_object($operand) || $caseSensitive) {
            $comparison = $this->qomFactory->comparison(
                $this->qomFactory->propertyValue($propertyName, '_entity'),
                QueryInterface::OPERATOR_EQUAL_TO,
                $operand
            );
        } else {
            $comparison = $this->qomFactory->comparison(
                $this->qomFactory->lowerCase(
                    $this->qomFactory->propertyValue($propertyName, '_entity')
                ),
                QueryInterface::OPERATOR_EQUAL_TO,
                strtolower($operand)
            );
        }

        return $comparison;
    }

    /**
     * Returns a like criterion used for matching objects against a query.
     * Matches if the property named $propertyName is like the $operand, using
     * standard SQL wildcards.
     *
     * @param string $propertyName The name of the property to compare against
     * @param string $operand The value to compare with
     * @param boolean $caseSensitive Whether the matching should be done case-sensitive
     * @return object
     * @throws InvalidQueryException if used on a non-string property
     * @api
     */
    public function like($propertyName, $operand, $caseSensitive = true)
    {
        if (!is_string($operand)) {
            throw new InvalidQueryException('Operand must be a string, was ' . gettype($operand), 1276781107);
        }
        if ($caseSensitive) {
            $comparison = $this->qomFactory->comparison(
                $this->qomFactory->propertyValue($propertyName, '_entity'),
                QueryInterface::OPERATOR_LIKE,
                $operand
            );
        } else {
            $comparison = $this->qomFactory->comparison(
                $this->qomFactory->lowerCase(
                    $this->qomFactory->propertyValue($propertyName, '_entity')
                ),
                QueryInterface::OPERATOR_LIKE,
                strtolower($operand)
            );
        }

        return $comparison;
    }

    /**
     * Returns a "contains" criterion used for matching objects against a query.
     * It matches if the multivalued property contains the given operand.
     *
     * If NULL is given as $operand, there will never be a match!
     *
     * @param string $propertyName The name of the multivalued property to compare against
     * @param mixed $operand The value to compare with
     * @return Qom\Comparison
     * @throws InvalidQueryException if used on a single-valued property
     * @api
     */
    public function contains($propertyName, $operand)
    {
        if (!$this->classSchema->isMultiValuedProperty($propertyName)) {
            throw new InvalidQueryException('Property "' . $propertyName . '" must be multi-valued', 1276781026);
        }
        return $this->qomFactory->comparison(
            $this->qomFactory->propertyValue($propertyName, '_entity'),
            QueryInterface::OPERATOR_CONTAINS,
            $operand
        );
    }

    /**
     * Returns an "isEmpty" criterion used for matching objects against a query.
     * It matches if the multivalued property contains no values or is NULL.
     *
     * @param string $propertyName The name of the multivalued property to check
     * @return boolean
     * @throws InvalidQueryException if used on a single-valued property
     * @api
     */
    public function isEmpty($propertyName)
    {
        if (!$this->classSchema->isMultiValuedProperty($propertyName)) {
            throw new InvalidQueryException('Property "' . $propertyName . '" must be multi-valued', 1276853547);
        }
        return $this->qomFactory->comparison(
            $this->qomFactory->propertyValue($propertyName, '_entity'),
            QueryInterface::OPERATOR_IS_EMPTY
        );
    }

    /**
     * Returns an "in" criterion used for matching objects against a query. It
     * matches if the property's value is contained in the multivalued operand.
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with, multivalued
     * @return Qom\Comparison
     * @throws InvalidQueryException if used on a multi-valued property or with single-valued operand
     * @api
     */
    public function in($propertyName, $operand)
    {
        if (!is_array($operand) && (!$operand instanceof \ArrayAccess) && (!$operand instanceof \Traversable)) {
            throw new InvalidQueryException('The "in" constraint must be given a multi-valued operand (array, ArrayAccess, Traversable).', 1264678095);
        }
        if ($this->classSchema->isMultiValuedProperty($propertyName)) {
            throw new InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued.', 1276777034);
        }

        return $this->qomFactory->comparison(
            $this->qomFactory->propertyValue($propertyName, '_entity'),
            QueryInterface::OPERATOR_IN,
            $operand
        );
    }

    /**
     * Returns a less than criterion used for matching objects against a query
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with
     * @return Qom\Comparison
     * @throws InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
     * @api
     */
    public function lessThan($propertyName, $operand)
    {
        if ($this->classSchema->isMultiValuedProperty($propertyName)) {
            throw new InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276784963);
        }
        if (!($operand instanceof \DateTimeInterface) && !TypeHandling::isLiteral(gettype($operand))) {
            throw new InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276784964);
        }

        return $this->qomFactory->comparison(
            $this->qomFactory->propertyValue($propertyName, '_entity'),
            QueryInterface::OPERATOR_LESS_THAN,
            $operand
        );
    }

    /**
     * Returns a less or equal than criterion used for matching objects against a query
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with
     * @return Qom\Comparison
     * @throws InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
     * @api
     */
    public function lessThanOrEqual($propertyName, $operand)
    {
        if ($this->classSchema->isMultiValuedProperty($propertyName)) {
            throw new InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276784943);
        }
        if (!($operand instanceof \DateTimeInterface) && !TypeHandling::isLiteral(gettype($operand))) {
            throw new InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276784944);
        }

        return $this->qomFactory->comparison(
            $this->qomFactory->propertyValue($propertyName, '_entity'),
            QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO,
            $operand
        );
    }

    /**
     * Returns a greater than criterion used for matching objects against a query
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with
     * @return Qom\Comparison
     * @throws InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
     * @api
     */
    public function greaterThan($propertyName, $operand)
    {
        if ($this->classSchema->isMultiValuedProperty($propertyName)) {
            throw new InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276774885);
        }
        if (!($operand instanceof \DateTimeInterface) && !TypeHandling::isLiteral(gettype($operand))) {
            throw new InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276774886);
        }

        return $this->qomFactory->comparison(
            $this->qomFactory->propertyValue($propertyName, '_entity'),
            QueryInterface::OPERATOR_GREATER_THAN,
            $operand
        );
    }

    /**
     * Returns a greater than or equal criterion used for matching objects against a query
     *
     * @param string $propertyName The name of the property to compare against
     * @param mixed $operand The value to compare with
     * @return Qom\Comparison
     * @throws InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
     * @api
     */
    public function greaterThanOrEqual($propertyName, $operand)
    {
        if ($this->classSchema->isMultiValuedProperty($propertyName)) {
            throw new InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276774883);
        }
        if (!($operand instanceof \DateTimeInterface) && !TypeHandling::isLiteral(gettype($operand))) {
            throw new InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276774884);
        }

        return $this->qomFactory->comparison(
            $this->qomFactory->propertyValue($propertyName, '_entity'),
            QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
            $operand
        );
    }
}

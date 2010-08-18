<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The Query classs used to run queries like
 * $query->matching($query->equals('foo', 'bar'))->setLimit(10)->execute();
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Query implements \F3\FLOW3\Persistence\QueryInterface {

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var \F3\FLOW3\Reflection\ClassSchema
	 */
	protected $classSchema;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Persistence\DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var \F3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \F3\FLOW3\Persistence\Qom\QueryObjectModelFactory
	 */
	protected $qomFactory;

	/**
	 * @var \F3\FLOW3\Persistence\Qom\Constraint
	 */
	protected $constraint;

	/**
	 * The property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => \F3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \F3\FLOW3\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @var array
	 */
	protected $orderings = array();

	/**
	 * @var integer
	 */
	protected $limit;

	/**
	 * @var integer
	 */
	protected $offset = 0;

	/**
	 * Constructs a query object working on the given type
	 *
	 * @param string $type
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($type, \F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->type = $type;
		$this->classSchema = $reflectionService->getClassSchema($type);
	}

	/**
	 * Injects the FLOW3 object factory
	 *
	 * @param \F3\FLOW3\Object\ObjectManagerInterface $qomFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ObjectManagerInterface $qomFactory) {
		$this->objectManager = $qomFactory;
	}

	/**
	 * Injects the DataMapper to map records to objects
	 *
	 * @param \F3\FLOW3\Persistence\DataMapper $dataMapper
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectDataMapper(\F3\FLOW3\Persistence\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Injects the persistence manager
	 *
	 * @param \F3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Injects the FLOW3 QOM factory
	 *
	 * @param \F3\FLOW3\Persistence\Qom\QueryObjectModelFactory $qomFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectQomFactory(\F3\FLOW3\Persistence\Qom\QueryObjectModelFactory $qomFactory) {
		$this->qomFactory = $qomFactory;
	}

	/**
	 * Executes the query and returns the result
	 *
	 * @return array The query result
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function execute() {
		return $this->dataMapper->mapToObjects($this->persistenceManager->getObjectDataByQuery($this));
	}

	/**
	 * Executes the number of matching objects for the query
	 *
	 * @return integer The number of matching objects
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function count() {
		return $this->persistenceManager->getObjectCountByQuery($this);
	}

	/**
	 * Returns the type this query cares for.
	 *
	 * @return string
 	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => \F3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \F3\FLOW3\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @param array $orderings The property names to order by
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setOrderings(array $orderings) {
		$this->orderings = $orderings;
		return $this;
	}

	/**
	 * Returns the property names to order the result by. Like this:
	 * array(
	 *  'foo' => \F3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \F3\FLOW3\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getOrderings() {
		return $this->orderings;
	}

	/**
	 * Sets the maximum size of the result set to limit. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param integer $limit
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setLimit($limit) {
		if ($limit < 1 || !is_int($limit)) {
			throw new \InvalidArgumentException('setLimit() accepts only integers greater 0.', 1263387249);
		}
		$this->limit = $limit;

		return $this;
	}

	/**
	 * Returns the maximum size of the result set to limit.
	 *
	 * @param integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getLimit() {
		return $this->limit;
	}


	/**
	 * Sets the start offset of the result set to $offset. Returns $this to
	 * allow for chaining (fluid interface)
	 *
	 * @param integer $offset
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function setOffset($offset) {
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * The constraint used to limit the result set. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param \F3\FLOW3\Persistence\Qom\Constraint $constraint
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 * @api
	 */
	public function matching($constraint) {
		$this->constraint = $constraint;
		return $this;
	}

	/**
	 * Gets the constraint for this query.
	 *
	 * @return \F3\FLOW3\Persistence\Qom\Constraint the constraint, or null if none
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	*/
	public function getConstraint() {
		return $this->constraint;
	}

	/**
	 * Performs a logical conjunction of the two given constraints. The method
	 * takes one or more contraints and concatenates them with a boolean AND.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @return \F3\FLOW3\Persistence\Qom\And
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function logicalAnd($constraint1) {
		if (is_array($constraint1)) {
			$resultingConstraint = array_shift($constraint1);
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
			$resultingConstraint = array_shift($constraints);
		}

		if ($resultingConstraint === NULL) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidNumberOfConstraintsException('There must be at least one constraint or a non-empty array of constraints given.', 1268056288);
		}

		foreach ($constraints as $constraint) {
			$resultingConstraint = $this->qomFactory->_and($resultingConstraint, $constraint);
		}
		return $resultingConstraint;
	}

	/**
	 * Performs a logical disjunction of the two given constraints. The method
	 * takes one or more contraints and concatenates them with a boolean OR.
	 * It also accepts a single array of constraints to be concatenated.
	 *
	 * @param object $constraint1 The first of multiple constraints or an array of constraints.
	 * @return \F3\FLOW3\Persistence\Qom\Or
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function logicalOr($constraint1) {
		if (is_array($constraint1)) {
			$resultingConstraint = array_shift($constraint1);
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
			$resultingConstraint = array_shift($constraints);
		}

		if ($resultingConstraint === NULL) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidNumberOfConstraintsException('There must be at least one constraint or a non-empty array of constraints given.', 1268056289);
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
	 * @return \F3\FLOW3\Persistence\Qom\Not
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function logicalNot($constraint) {
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Decide what to do about equality on multi-valued properties
	 * @api
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE) {
		if ($operand === NULL) {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->propertyValue($propertyName, '_entity'),
				\F3\FLOW3\Persistence\QueryInterface::OPERATOR_IS_NULL
			);
		} elseif (is_object($operand) || $caseSensitive) {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->propertyValue($propertyName, '_entity'),
				\F3\FLOW3\Persistence\QueryInterface::OPERATOR_EQUAL_TO,
				$operand
			);
		} else {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->lowerCase(
					$this->qomFactory->propertyValue($propertyName, '_entity')
				),
				\F3\FLOW3\Persistence\QueryInterface::OPERATOR_EQUAL_TO,
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
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a non-string property
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function like($propertyName, $operand, $caseSensitive = TRUE) {
		if (!is_string($operand)) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Operand must be a string, was ' . gettype($operand), 1276781107);
		}
		if ($caseSensitive) {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->propertyValue($propertyName, '_entity'),
				\F3\FLOW3\Persistence\QueryInterface::OPERATOR_LIKE,
				$operand
			);
		} else {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->lowerCase(
					$this->qomFactory->propertyValue($propertyName, '_entity')
				),
				\F3\FLOW3\Persistence\QueryInterface::OPERATOR_LIKE,
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
	 * @return \F3\FLOW3\Persistence\Qom\Comparison
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function contains($propertyName, $operand){
		if (!$this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must be multi-valued', 1276781026);
		}
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\F3\FLOW3\Persistence\QueryInterface::OPERATOR_CONTAINS,
			$operand
		);
	}

	/**
	 * Returns an "isEmpty" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains no values or is NULL.
	 *
	 * @param string $propertyName The name of the multivalued property to check
	 * @return boolean
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 * @api
	 */
	public function isEmpty($propertyName) {
		if (!$this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must be multi-valued', 1276853547);
		}
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\F3\FLOW3\Persistence\QueryInterface::OPERATOR_IS_EMPTY
		);
	}

	/**
	 * Returns an "in" criterion used for matching objects against a query. It
	 * matches if the property's value is contained in the multivalued operand.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with, multivalued
	 * @return \F3\FLOW3\Persistence\Qom\Comparison
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with single-valued operand
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function in($propertyName, $operand) {
		if (!is_array($operand) && (!$operand instanceof \ArrayAccess) && (!$operand instanceof \Traversable)) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('The "in" constraint must be given a multi-valued operand (array, ArrayAccess, Traversable).', 1264678095);
		}
		if ($this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued.', 1276777034);
		}

		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\F3\FLOW3\Persistence\QueryInterface::OPERATOR_IN,
			$operand
		);
	}

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\Qom\Comparison
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function lessThan($propertyName, $operand) {
		if ($this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276784963);
		}
		if (!($operand instanceof \DateTime) && !\F3\FLOW3\Utility\TypeHandling::isLiteral(gettype($operand))) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276784964);
		}

		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\F3\FLOW3\Persistence\QueryInterface::OPERATOR_LESS_THAN,
			$operand
		);
	}

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\Qom\Comparison
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function lessThanOrEqual($propertyName, $operand) {
		if ($this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276784943);
		}
		if (!($operand instanceof \DateTime) && !\F3\FLOW3\Utility\TypeHandling::isLiteral(gettype($operand))) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276784944);
		}

		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\F3\FLOW3\Persistence\QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO,
			$operand
		);
	}

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\Qom\Comparison
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function greaterThan($propertyName, $operand) {
		if ($this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276774885);
		}
		if (!($operand instanceof \DateTime) && !\F3\FLOW3\Utility\TypeHandling::isLiteral(gettype($operand))) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276774886);
		}

		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\F3\FLOW3\Persistence\QueryInterface::OPERATOR_GREATER_THAN,
			$operand
		);
	}

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\Qom\Comparison
	 * @throws \F3\FLOW3\Persistence\Exception\InvalidQueryException if used on a multi-valued property or with a non-literal/non-DateTime operand
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function greaterThanOrEqual($propertyName, $operand) {
		if ($this->classSchema->isMultiValuedProperty($propertyName)) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Property "' . $propertyName . '" must not be multi-valued', 1276774883);
		}
		if (!($operand instanceof \DateTime) && !\F3\FLOW3\Utility\TypeHandling::isLiteral(gettype($operand))) {
			throw new \F3\FLOW3\Persistence\Exception\InvalidQueryException('Operand must be a literal or DateTime, was ' . gettype($operand), 1276774884);
		}

		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\F3\FLOW3\Persistence\QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
			$operand
		);
	}

}
?>
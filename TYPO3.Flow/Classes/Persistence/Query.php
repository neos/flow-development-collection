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
 * @version $Id$
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
	 * @var \F3\FLOW3\Object\ObjectFactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Persistence\DataMapperInterface
	 */
	protected $dataMapper;

	/**
	 * @var \F3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \F3\FLOW3\Persistence\QOM\QueryObjectModelFactory
	 */
	protected $qomFactory;

	/**
	 * @var \F3\FLOW3\Persistence\QOM\Constraint
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct($type) {
		$this->type = $type;
	}

	/**
	 * Injects the FLOW3 object factory
	 *
	 * @param \F3\FLOW3\Object\ObjectFactoryInterface $qomFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\ObjectFactoryInterface $qomFactory) {
		$this->objectFactory = $qomFactory;
	}

	/**
	 * Injects the DataMapper to map records to objects
	 *
	 * @param \F3\FLOW3\Persistence\DataMapperInterface $dataMapper
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectDataMapper(\F3\FLOW3\Persistence\DataMapperInterface $dataMapper) {
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
	 * @param \F3\FLOW3\Persistence\QOM\QueryObjectModelFactory $qomFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectQomFactory(\F3\FLOW3\Persistence\QOM\QueryObjectModelFactory $qomFactory) {
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
		return $this->dataMapper->mapToObjects($this->persistenceManager->getBackend()->getObjectDataByQuery($this));
	}

	/**
	 * Executes the number of matching objects for the query
	 *
	 * @return integer The number of matching objects
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function count() {
		return $this->persistenceManager->getBackend()->getObjectCountByQuery($this);
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
	 * @param \F3\FLOW3\Persistence\QOM\Constraint $constraint
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
	 * @return \F3\FLOW3\Persistence\QOM\Constraint the constraint, or null if none
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	*/
	public function getConstraint() {
		return $this->constraint;
	}

	/**
	 * Performs a logical conjunction of the two given constraints.
	 *
	 * @param object $constraint1 First constraint
	 * @param object $constraint2 Second constraint
	 * @return \F3\FLOW3\Persistence\QOM\And
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function logicalAnd($constraint1, $constraint2) {
		return $this->qomFactory->_and(
			$constraint1,
			$constraint2
		);
	}

	/**
	 * Performs a logical disjunction of the two given constraints
	 *
	 * @param object $constraint1 First constraint
	 * @param object $constraint2 Second constraint
	 * @return \F3\FLOW3\Persistence\QOM\Or
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function logicalOr($constraint1, $constraint2) {
		return $this->qomFactory->_or(
			$constraint1,
			$constraint2
		);
	}

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 * @return \F3\FLOW3\Persistence\QOM\Not
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function logicalNot($constraint) {
		return $this->qomFactory->not($constraint);
	}

	/**
	 * Returns an equals criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the equality test should be done case-sensitive
	 * @return \F3\FLOW3\Persistence\QOM\Comparison
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE) {
		if (is_object($operand) || $caseSensitive) {
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
	 * Returns a like criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\QOM\Comparison
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function like($propertyName, $operand) {
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\F3\FLOW3\Persistence\QueryInterface::OPERATOR_LIKE,
			$operand
		);
	}

	/**
	 * Returns a "contains" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains the given operand.
	 *
	 * @param string $propertyName The name of the (multivalued) property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \F3\FLOW3\Persistence\QOM\Comparison
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function contains($propertyName, $operand){
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\F3\FLOW3\Persistence\QueryInterface::OPERATOR_CONTAINS,
			$operand
		);
	}

	/**
	 * Returns an "in" criterion used for matching objects against a query. It
	 * matches if the property's value is contained in the multivalued operand.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with, multivalued
	 * @return \F3\FLOW3\Persistence\QOM\Comparison
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function in($propertyName, $operand) {
		if (!is_array($operand) && (!$operand instanceof \ArrayAccess) && (!$operand instanceof \Traversable)) {
			throw new \F3\FLOW3\Persistence\Exception\UnexpectedTypeException('The "in" operator must be given a mutlivalued operand (array, ArrayAccess, Traversable).', 1264678095);
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
	 * @return \F3\FLOW3\Persistence\QOM\Comparison
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function lessThan($propertyName, $operand) {
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
	 * @return \F3\FLOW3\Persistence\QOM\Comparison
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function lessThanOrEqual($propertyName, $operand) {
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
	 * @return \F3\FLOW3\Persistence\QOM\Comparison
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function greaterThan($propertyName, $operand) {
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
	 * @return \F3\FLOW3\Persistence\QOM\Comparison
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function greaterThanOrEqual($propertyName, $operand) {
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, '_entity'),
			\F3\FLOW3\Persistence\QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
			$operand
		);
	}

}
?>
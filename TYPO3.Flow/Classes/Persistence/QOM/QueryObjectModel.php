<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\QOM;

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
 * A query in the FLOW3 query object model.
 *
 * The FLOW query object model describes the queries that can be evaluated by
 * the FLOW3 persistence layer.
 *
 * It is loosely based on the QOM defined in JSR-283.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class QueryObjectModel extends \F3\FLOW3\Persistence\Query {

	/**
	 * @var \F3\FLOW3\Persistence\QOM\Selector
	 */
	protected $selector;

	/**
	 * @var \F3\FLOW3\Persistence\QOM\Constraint
	 */
	protected $constraint;

	/**
	 * @var array
	 */
	protected $orderings;

	/**
	 * @var array
	 */
	protected $columns;

	/**
	 * Constructs this QueryObjectModel instance
	 *
	 * @param \F3\FLOW3\Persistence\QOM\Selector $selector
	 * @param \F3\FLOW3\Persistence\QOM\Constraint $constraint (null if none)
	 * @param array $orderings
	 * @param array $columns
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Persistence\QOM\Selector $selector, \F3\FLOW3\Persistence\QOM\Constraint $constraint, array $orderings, array $columns) {
		$this->selector = $selector;
		$this->constraint = $constraint;
		$this->orderings = $orderings;
		$this->columns = $columns;

		if ($this->constraint !== NULL) {
			$this->constraint->collectBoundVariableNames($this->boundVariables);
		}
	}

	/**
	 * Gets the selector for this query.
	 *
	 * @return \F3\FLOW3\Persistence\QOM\Selector the selector; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	*/
	public function getSelector() {
		return $this->selector;
	}

	/**
	 * Gets the constraint for this query.
	 *
	 * @return \F3\FLOW3\Persistence\QOM\ConstraintInterface the constraint, or null if none
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	*/
	public function getConstraint() {
		return $this->constraint;
	}

	/**
	 * Gets the orderings for this query.
	 *
	 * @return array an array of zero or more \F3\FLOW3\Persistence\QOM\OrderingInterface; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	*/
	public function getOrderings() {
		return $this->orderings;
	}

}
?>
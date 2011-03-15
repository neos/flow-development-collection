<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Generic\Qom;

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
 * Performs a logical disjunction of two other constraints.
 *
 * To satisfy the Or constraint, the tuple must either:
 *  satisfy constraint1 but not constraint2, or
 *  satisfy constraint2 but not constraint1, or
 *  satisfy both constraint1 and constraint2.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class LogicalOr extends \F3\FLOW3\Persistence\Generic\Qom\Constraint {

	/**
	 * @var \F3\FLOW3\Persistence\Generic\Qom\Constraint
	 */
	protected $constraint1;

	/**
	 * @var \F3\FLOW3\Persistence\Generic\Qom\Constraint
	 */
	protected $constraint2;

	/**
	 *
	 * @param \F3\FLOW3\Persistence\Generic\Qom\Constraint $constraint1
	 * @param \F3\FLOW3\Persistence\Generic\Qom\Constraint $constraint2
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Persistence\Generic\Qom\Constraint $constraint1, \F3\FLOW3\Persistence\Generic\Qom\Constraint $constraint2) {
		$this->constraint1 = $constraint1;
		$this->constraint2 = $constraint2;
	}

	/**
	 * Gets the first constraint.
	 *
	 * @return \F3\FLOW3\Persistence\Generic\Qom\Constraint the constraint; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getConstraint1() {
		return $this->constraint1;
	}

	/**
	 * Gets the second constraint.
	 *
	 * @return \F3\FLOW3\Persistence\Generic\Qom\Constraint the constraint; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getConstraint2() {
		return $this->constraint2;
	}

}
?>
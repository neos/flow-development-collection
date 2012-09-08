<?php
namespace TYPO3\FLOW3\Persistence\Generic\Qom;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
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
 * @api
 */
class LogicalOr extends \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint {

	/**
	 * @var \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint
	 */
	protected $constraint1;

	/**
	 * @var \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint
	 */
	protected $constraint2;

	/**
	 *
	 * @param \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint $constraint1
	 * @param \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint $constraint2
	 */
	public function __construct(\TYPO3\FLOW3\Persistence\Generic\Qom\Constraint $constraint1, \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint $constraint2) {
		$this->constraint1 = $constraint1;
		$this->constraint2 = $constraint2;
	}

	/**
	 * Gets the first constraint.
	 *
	 * @return \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint the constraint; non-null
	 * @api
	 */
	public function getConstraint1() {
		return $this->constraint1;
	}

	/**
	 * Gets the second constraint.
	 *
	 * @return \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint the constraint; non-null
	 * @api
	 */
	public function getConstraint2() {
		return $this->constraint2;
	}

}
?>
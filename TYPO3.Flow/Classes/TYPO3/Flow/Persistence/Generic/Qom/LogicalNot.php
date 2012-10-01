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
 * Performs a logical negation of another constraint.
 *
 * To satisfy the Not constraint, the tuple must not satisfy $constraint.
 *
 * @api
 */
class LogicalNot extends \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint {

	/**
	 * @var \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint
	 */
	protected $constraint;

	/**
	 *
	 * @param \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint $constraint
	 */
	public function __construct(\TYPO3\FLOW3\Persistence\Generic\Qom\Constraint $constraint) {
		$this->constraint = $constraint;
	}

	/**
	 * Gets the constraint negated by this Not constraint.
	 *
	 * @return \TYPO3\FLOW3\Persistence\Generic\Qom\Constraint the constraint; non-null
	 * @api
	 */
	public function getConstraint() {
		return $this->constraint;
	}

}
?>
<?php
namespace TYPO3\Eel\FlowQuery;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Contract for a *FlowQuery operation* which is applied onto a set of objects.
 *
 * @api
 */
interface OperationInterface {

	/**
	 * @return string the short name of the operation
	 * @api
	 */
	static public function getShortName();

	/**
	 * @return integer the priority of the operation
	 * @api
	 */
	static public function getPriority();

	/**
	 * @return boolean TRUE if the operation is final, FALSE otherwise
	 * @api
	 */
	static public function isFinal();

	/**
	 * This method is called to determine whether the operation
	 * can work with the $context objects. It can be implemented
	 * to implement runtime conditions.
	 *
	 * @param array (or array-like object) $context onto which this operation should be applied
	 * @return boolean TRUE if the operation can be applied onto the $context, FALSE otherwise
	 * @api
	 */
	public function canEvaluate($context);

	/**
	 * Evaluate the operation on the objects inside $flowQuery->getContext(),
	 * taking the $arguments into account.
	 *
	 * The resulting operation results should be stored using $flowQuery->setContext().
	 *
	 * If the operation is final, evaluate should directly return the operation result.
	 *
	 * @param \TYPO3\Eel\FlowQuery\FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the arguments for this operation
	 * @return mixed|null if the operation is final, the return value
	 */
	public function evaluate(\TYPO3\Eel\FlowQuery\FlowQuery $flowQuery, array $arguments);
}

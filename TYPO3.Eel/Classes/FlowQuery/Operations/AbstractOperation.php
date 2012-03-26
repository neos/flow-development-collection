<?php
namespace TYPO3\Eel\FlowQuery\Operations;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Eel".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Convenience base class for FlowQuery Operations. You should set
 * $shortName and optionally also $final and $priority when subclassing.
 *
 * @api
 */
abstract class AbstractOperation implements \TYPO3\Eel\FlowQuery\OperationInterface {

	/**
	 * The short name of the operation
	 *
	 * @var string
	 * @api
	 */
	static protected $shortName = NULL;

	/**
	 * The priority of operations. higher numbers override lower ones.
	 *
	 * @var integer
	 * @api
	 */
	static protected $priority = 1;

	/**
	 * If TRUE, the operation is final, i.e. directly executed.
	 *
	 * @var boolean
	 * @api
	 */
	static protected $final = FALSE;

	static public function getPriority() {
		return static::$priority;
	}

	static public function isFinal() {
		return static::$final;
	}

	static public function getShortName() {
		if (!is_string(static::$shortName)) {
			throw new \TYPO3\Eel\FlowQuery\FlowQueryException('Short name in class ' . __CLASS__ . ' is empty.', 1332488549);
		}
		return static::$shortName;
	}

	public function canEvaluate($context) {
		return TRUE;
	}
}
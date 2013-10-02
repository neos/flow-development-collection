<?php
namespace TYPO3\Eel\FlowQuery;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


use TYPO3\Flow\Annotations as Flow;

/**
 * FlowQuery Operation Resolver Interface
 */
interface OperationResolverInterface {

	/**
	 * @param string $operationName
	 * @return boolean TRUE if $operationName is final
	 */
	public function isFinalOperation($operationName);

	/**
	 * Resolve an operation, taking runtime constraints into account.
	 *
	 * @param string      $operationName
	 * @param array|mixed $context
	 * @return OperationInterface the resolved operation
	 */
	public function resolveOperation($operationName, $context);

	/**
	 * @param string $operationName
	 * @return boolean
	 */
	public function hasOperation($operationName);
}
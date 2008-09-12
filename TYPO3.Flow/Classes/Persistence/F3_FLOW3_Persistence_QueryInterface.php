<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Persistence;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * A persistence query interface
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface QueryInterface {

	/**
	 * Executes the query against the backend and returns the result
	 *
	 * @return array The query result, an array of objects
	 */
	public function execute();

	/**
	 * The constraint used to limit the result set
	 *
	 * @param mixed $constraint Some constraint, depending on the backend
	 * @return F3::FLOW3::Persistence::QueryInterface
	 */
	public function matching($constraint);

	/**
	 * Returns an equals criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3::FLOW3::Persistence::OperatorInterface
	 */
	public function equals($property, $operand);

	/**
	 * Returns a like criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3::FLOW3::Persistence::OperatorInterface
	 */
	public function like($property, $operand);

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3::FLOW3::Persistence::OperatorInterface
	 */
	public function lessThan($property, $operand);

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3::FLOW3::Persistence::OperatorInterface
	 */
	public function lessThanOrEqual($property, $operand);

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3::FLOW3::Persistence::OperatorInterface
	 */
	public function greaterThan($property, $operand);

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $property The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return F3::FLOW3::Persistence::OperatorInterface
	 */
	public function greaterThanOrEqual($property, $operand);

}
?>
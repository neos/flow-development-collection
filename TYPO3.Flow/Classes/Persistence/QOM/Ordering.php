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
 * An operand to a binary operation specified by a Comparison.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Ordering {

	/**
	 * Construct an Ordering instance.
	 *
	 * @param \F3\FLOW3\Persistence\QOM\DynamicOperand $operand
	 * @param string $order either QueryInterface.ORDER_ASCENDING or QueryInterface.ORDER_DESCENDING
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Persistence\QOM\DynamicOperand $operand, $order) {
		if ($order !== \F3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING
			&& $order !== \F3\FLOW3\Persistence\QueryInterface::ORDER_DESCENDING) {
				throw new \InvalidArgumentException('Illegal order requested.', 1260291954);
			}
		$this->operand = $operand;
		$this->order = $order;
	}

	/**
	 * The operand by which to order.
	 *
	 * @return \F3\FLOW3\Persistence\QOM\DynamicOperand the operand; non-null
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getOperand() {
		return $this->operand;
	}

	/**
	 * Gets the order.
	 *
	 * @return string either QueryInterface.ORDER_ASCENDING or QueryInterface.ORDER_DESCENDING
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getOrder() {
		return $this->order;
	}

}

?>
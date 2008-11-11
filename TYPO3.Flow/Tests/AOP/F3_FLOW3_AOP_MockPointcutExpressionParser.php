<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::AOP;

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
 * @subpackage AOP
 * @version $Id$
 */

/**
 * A mock pointcut expression parser - used to test the real pointcut expression parser
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:F3::FLOW3::AOP::PointcutExpressionParser.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class MockPointcutExpressionParser extends F3::FLOW3::AOP::PointcutExpressionParser {

	/**
	 * Factory method for creating custom filter instances
	 *
	 * @param string Object name of the filter
	 * @return object An instance of the filter object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function createCustomFilter($filterObjectName) {
		return new $filterObjectName;
	}
}
?>
<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP;

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
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 */

/**
 * A marker interface and contract for pointcuts
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id:\F3\FLOW3\AOP\PointcutInterface.php 201 2007-03-30 11:18:30Z robert $
 * @author Robert Lemke <robert@typo3.org>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
interface PointcutInterface {

	/**
	 * The constructor
	 *
	 * @param string $pointcutExpression A pointcut expression which configures the pointcut
	 * @param \F3\FLOW3\AOP\PointcutExpressionParserInterface $pointcutExpressionParser: The parser to use for parsing the pointcut expression
	 * @param string $aspectClassName  The name of the aspect class where the pointcut was declared (either explicitly or from an advice's pointcut expression)
	 * @param string $pointcutMethodName (optional) If the pointcut is created from a pointcut declaration, the name of the method declaring the pointcut must be passed
	 * @return void
	 */
	public function __construct($pointcutExpression, \F3\FLOW3\AOP\PointcutExpressionParser $pointcutExpressionParser, $aspectClassName, $pointcutMethodName = NULL);

	/**
	 * Checks if the given class and method match this pointcut.
	 * Before each match run, reset() must be called to reset the circular references guard.
	 *
	 * @param \F3\FLOW3\Reflection\ClassReflection $class Class to check against
	 * @param \F3\FLOW3\Reflection\Methd $method Method to check against
	 * @param mixed $pointcutQueryIdentifier: Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if class and method match this point cut, otherwise FALSE
	 */
	public function matches(\F3\FLOW3\Reflection\ClassReflection $class, \F3\FLOW3\Reflection\MethodReflection $method, $pointcutQueryIdentifier);

	/**
	 * Returns the pointcut expression which has been passed to the constructor.
	 * This can be used for debugging pointcuts.
	 *
	 * @return string The pointcut expression
	 */
	public function getPointcutExpression();

	/**
	 * Returns the aspect class name where the pointcut was declared.
	 *
	 * @return string The aspect class name where the pointcut was declared
	 */
	public function getAspectClassName();

	/**
	 * Returns the pointcut method name (if any was defined)
	 *
	 * @return string The pointcut method name
	 */
	public function getPointcutMethodName();
}

?>
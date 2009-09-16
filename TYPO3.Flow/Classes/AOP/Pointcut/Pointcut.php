<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\AOP\Pointcut;

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
 * The pointcut defines the set of join points (ie. "situations") in which certain
 * code associated with the pointcut (ie. advices) should be executed. This set of
 * join points is defined by a poincut expression which is matched against class
 * and method signatures.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Pointcut {

	const MAXIMUM_RECURSIONS = 99;

	/**
	 * A pointcut expression which configures the pointcut
	 * @var string
	 */
	protected $pointcutExpression;

	/**
	 * The filter composite object, created from the pointcut expression
	 * @var \F3\FLOW3\AOP\Pointcut\PointcutFilterComposite
	 */
	protected $pointcutFilterComposite;

	/**
	 * If this pointcut is based on a pointcut declaration, contains the name of the aspect class where the pointcut was declared
	 * @var string
	 */
	protected $aspectClassName;

	/**
	 * If this pointcut is based on a pointcut declaration, contains the name of the method acting as the pointcut identifier
	 * @var string
	 */
	protected $pointcutMethodName;

	/**
	 * An identifier which is used to detect circular references between pointcuts
	 * @var mixed
	 */
	protected $pointcutQueryIdentifier = NULL;

	/**
	 * Counts how often this pointcut's matches() method has been called during one query
	 * @var integer
	 */
	protected $recursionLevel = 0;

	/**
	 * The constructor
	 *
	 * @param string $pointcutExpression A pointcut expression which configures the pointcut
	 * @param \F3\FLOW3\AOP\Pointcut\PointcutFilterComposite $pointcutFilterComposite
	 * @param string $aspectClassName The name of the aspect class where the pointcut was declared (either explicitly or from an advice's pointcut expression)
	 * @param string $pointcutMethodName (optional) If the pointcut is created from a pointcut declaration, the name of the method declaring the pointcut must be passed
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($pointcutExpression, \F3\FLOW3\AOP\Pointcut\PointcutFilterComposite $pointcutFilterComposite, $aspectClassName, $pointcutMethodName = NULL) {
		$this->pointcutExpression = $pointcutExpression;
		$this->pointcutFilterComposite = $pointcutFilterComposite;
		$this->aspectClassName = $aspectClassName;
		$this->pointcutMethodName = $pointcutMethodName;
	}

	/**
	 * Checks if the given class and method match this pointcut.
	 * Before each match run, reset() must be called to reset the circular references guard.
	 *
	 * @param string $className Class to check against
	 * @param string $methodName Method to check against
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if class and method match this point cut, otherwise FALSE
	 * @throws F3\FLOW3\AOP\Exception\CircularPointcutReference if a circular pointcut reference was detected
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		if ($this->pointcutQueryIdentifier === $pointcutQueryIdentifier) {
			$this->recursionLevel ++;
			if ($this->recursionLevel > self::MAXIMUM_RECURSIONS) {
				throw new \F3\FLOW3\AOP\Exception\CircularPointcutReference('Circular pointcut reference detected in ' . $this->aspectClassName . '->' . $this->pointcutMethodName . ', too many recursions (Query identifier: ' . $pointcutQueryIdentifier . ').', 1172416172);
			}
		} else {
			$this->pointcutQueryIdentifier = $pointcutQueryIdentifier;
			$this->recursionLevel = 0;
		}
		return $this->pointcutFilterComposite->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
	}

	/**
	 * Returns the pointcut expression which has been passed to the constructor.
	 * This can be used for debugging pointcuts.
	 *
	 * @return string The pointcut expression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcutExpression() {
		return $this->pointcutExpression;
	}

	/**
	 * Returns the aspect class name where the pointcut was declared.
	 *
	 * @return string The aspect class name where the pointcut was declared
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAspectClassName() {
		return $this->aspectClassName;
	}

	/**
	 * Returns the pointcut method name (if any was defined)
	 *
	 * @return string The pointcut method name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPointcutMethodName() {
		return $this->pointcutMethodName;
	}
}
?>
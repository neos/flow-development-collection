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
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 */

/**
 * A filter which refers to another pointcut.
 *
 * @package FLOW3
 * @subpackage AOP
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class PointcutFilter implements \F3\FLOW3\AOP\Pointcut\PointcutFilterInterface {

	/**
	 * Name of the aspect class where the pointcut was declared
	 * @var string
	 */
	protected $aspectClassName;

	/**
	 * Name of the pointcut method
	 * @var string
	 */
	protected $pointcutMethodName;

	/**
	 * The pointcut this filter is based on
	 * @var \F3\FLOW3\AOP\Pointcut\Pointcut
	 */
	protected $pointcut;

	/**
	 * A reference to the AOP Framework
	 * @var \F3\FLOW3\AOP\Framewor
	 */
	protected $aopFramework;

	/**
	 * The constructor - initializes the pointcut filter with the name of the pointcut we're refering to
	 *
	 * @param string $aspectClassName Name of the aspect class containing the pointcut
	 * @param string $pointcutMethodName Name of the method which acts as an anchor for the pointcut name and expression
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function __construct($aspectClassName, $pointcutMethodName) {
		$this->aspectClassName = $aspectClassName;
		$this->pointcutMethodName = $pointcutMethodName;
	}

	/**
	 * Injects the AOP Framework
	 *
	 * @param \F3\FLOW3\AOP\Framework $aopFramework
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectAOPFramework(\F3\FLOW3\AOP\Framework $aopFramework) {
		$this->aopFramework = $aopFramework;
	}

	/**
	 * Checks if the specified class and method matches with the pointcut
	 *
	 * @param string $className Name of the class to check against
	 * @param string $methodName Name of the method - not used here
	 * @param string $methodDeclaringClassName Name of the class the method was originally declared in
	 * @param mixed $pointcutQueryIdentifier Some identifier for this query - must at least differ from a previous identifier. Used for circular reference detection.
	 * @return boolean TRUE if the class matches, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		if ($this->pointcut === NULL) {
			$this->pointcut = $this->aopFramework->findPointcut($this->aspectClassName, $this->pointcutMethodName);
		}
		if ($this->pointcut === FALSE) throw new \F3\FLOW3\AOP\Exception\UnknownPointcut('No pointcut "' . $this->pointcutMethodName . '" found in aspect class "' . $this->aspectClassName . '" .', 1172223694);
		return $this->pointcut->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
	}
}

?>
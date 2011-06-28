<?php
namespace TYPO3\FLOW3\AOP\Pointcut;

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
 * A filter which refers to another pointcut.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @proxy disable
 */
class PointcutFilter implements \TYPO3\FLOW3\AOP\Pointcut\PointcutFilterInterface {

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
	 * @var \TYPO3\FLOW3\AOP\Pointcut\Pointcut
	 */
	protected $pointcut;

	/**
	 * A reference to the AOP Proxy ClassBuilder
	 * @var \TYPO3\FLOW3\AOP\Builder\ProxyClassBuilder
	 */
	protected $proxyClassBuilder;

	/**
	 * The constructor - initializes the pointcut filter with the name of the pointcut we're refering to
	 *
	 * @param string $aspectClassName Name of the aspect class containing the pointcut
	 * @param string $pointcutMethodName Name of the method which acts as an anchor for the pointcut name and expression
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($aspectClassName, $pointcutMethodName) {
		$this->aspectClassName = $aspectClassName;
		$this->pointcutMethodName = $pointcutMethodName;
	}

	/**
	 * Injects the AOP Proxy Class Builder
	 *
	 * @param \TYPO3\FLOW3\AOP\Builder\ProxyClassBuilder $proxyClassBuilder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectProxyClassBuilder(\TYPO3\FLOW3\AOP\Builder\ProxyClassBuilder $proxyClassBuilder) {
		$this->proxyClassBuilder = $proxyClassBuilder;
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
	 */
	public function matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier) {
		if ($this->pointcut === NULL) {
			$this->pointcut = $this->proxyClassBuilder->findPointcut($this->aspectClassName, $this->pointcutMethodName);
		}
		if ($this->pointcut === FALSE) throw new \TYPO3\FLOW3\AOP\Exception\UnknownPointcutException('No pointcut "' . $this->pointcutMethodName . '" found in aspect class "' . $this->aspectClassName . '" .', 1172223694);
		return $this->pointcut->matches($className, $methodName, $methodDeclaringClassName, $pointcutQueryIdentifier);
	}

	/**
	 * Returns TRUE if this filter holds runtime evaluations for a previously matched pointcut
	 *
	 * @return boolean TRUE if this filter has runtime evaluations
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRuntimeEvaluationsDefinition() {
		return $this->pointcut->hasRuntimeEvaluationsDefinition();
	}

	/**
	 * Returns runtime evaluations for the pointcut.
	 *
	 * @return array Runtime evaluations
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRuntimeEvaluationsDefinition() {
		if ($this->pointcut === NULL) {
			$this->pointcut = $this->proxyClassBuilder->findPointcut($this->aspectClassName, $this->pointcutMethodName);
		}
		if ($this->pointcut === FALSE) return array();

		return $this->pointcut->getRuntimeEvaluationsDefinition();
	}
}

?>
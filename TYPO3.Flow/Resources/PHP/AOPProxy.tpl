<?php
declare(encoding = 'utf-8');

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
 * AOP Proxy for the class "###TARGET_CLASS###".
 *
###CLASS_ANNOTATIONS### */
class ###TARGET_CLASS######PROXY_CLASS_SUFFIX### extends ###TARGET_CLASS### implements ###INTRODUCED_INTERFACES###T3_FLOW3_AOP_ProxyInterface {

	/**
	 * @var array An array of target method names and their advices grouped by advice type
	 */
	protected $targetMethodsAndGroupedAdvices = array();

	/**
	 * @var array An array of method names and the advices grouped by advice type in the order of their invocation
	 */
	protected $groupedAdviceChains = array();

	/**
	 * @var array An array of method names and their state: If set to TRUE, an advice for that method is currently being executed
	 */
	protected $methodIsInAdviceMode = array();

	/**
	 * @var T3_FLOW3_Component_ManagerInterface A reference to the component manager
	 */
	protected $componentManager;

###METHODS_INTERCEPTOR_CODE###

	/**
	 * Invokes the joinpoint - calls the target methods.
	 *
	 * @param  T3_FLOW3_AOP_JoinPointInterface: The join point
	 * @return mixed                           Result of the target (ie. original) method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invokeJoinPoint(T3_FLOW3_AOP_JoinPointInterface $joinPoint) {
		if (isset($this->methodIsInAdviceMode[$joinPoint->getMethodName()])) {
			return call_user_func_array(array($this, $joinPoint->getMethodName()), $joinPoint->getMethodArguments());
		}
	}

	/**
	 * Returns the advice chains (if any) grouped by advice type for a join point.
	 * Advice chains are only used in combination with Around advices.
	 *
	 * @param  string						$methodName: Method to return the advice chains for
	 * @return mixed						The advice chains  (array of T3_FLOW3_AOP_AdviceChain) or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getAdviceChains($methodName) {
		$adviceChains = NULL;
		if (is_array($this->groupedAdviceChains)) {
			if (isset($this->groupedAdviceChains[$methodName])) {
				$adviceChains = $this->groupedAdviceChains[$methodName];
			} else {
				if (isset($this->targetMethodsAndGroupedAdvices[$methodName])) {
					$groupedAdvices = $this->targetMethodsAndGroupedAdvices[$methodName];
					if (isset($groupedAdvices['T3_FLOW3_AOP_AroundAdvice'])) {
						$this->groupedAdviceChains[$methodName]['T3_FLOW3_AOP_AroundAdvice'] = new T3_FLOW3_AOP_AdviceChain($groupedAdvices['T3_FLOW3_AOP_AroundAdvice'], $this);
						$adviceChains = $this->groupedAdviceChains[$methodName];
					}
				}
			}
		}
		return $adviceChains;
	}
}
?>
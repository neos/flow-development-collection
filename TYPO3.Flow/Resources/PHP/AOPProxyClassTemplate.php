/**
 * AOP Proxy for the class "###TARGET_CLASS_NAME###".
 *
###CLASS_ANNOTATIONS### */
class ###PROXY_CLASS_NAME### extends ###TARGET_CLASS_NAME### implements ###INTRODUCED_INTERFACES###F3_FLOW3_AOP_ProxyInterface {

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
	 * @var F3_FLOW3_Component_ManagerInterface A reference to the component manager
	 */
	protected $componentManager;

###METHODS_INTERCEPTOR_CODE###

	/**
	 * Invokes the joinpoint - calls the target methods.
	 *
	 * @param F3_FLOW3_AOP_JoinPointInterface: The join point
	 * @return mixed Result of the target (ie. original) method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invokeJoinPoint(F3_FLOW3_AOP_JoinPointInterface $joinPoint) {
		if (isset($this->methodIsInAdviceMode[$joinPoint->getMethodName()])) {
			return call_user_func_array(array($this, $joinPoint->getMethodName()), $joinPoint->getMethodArguments());
		}
	}

	/**
	 * Returns the advice chains (if any) grouped by advice type for a join point.
	 * Advice chains are only used in combination with Around advices.
	 *
	 * @param string $methodName: Method to return the advice chains for
	 * @return mixed The advice chains  (array of F3_FLOW3_AOP_AdviceChain) or NULL
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
					if (isset($groupedAdvices['F3_FLOW3_AOP_AroundAdvice'])) {
						$this->groupedAdviceChains[$methodName]['F3_FLOW3_AOP_AroundAdvice'] = new F3_FLOW3_AOP_AdviceChain($groupedAdvices['F3_FLOW3_AOP_AroundAdvice'], $this);
						$adviceChains = $this->groupedAdviceChains[$methodName];
					}
				}
			}
		}
		return $adviceChains;
	}
}
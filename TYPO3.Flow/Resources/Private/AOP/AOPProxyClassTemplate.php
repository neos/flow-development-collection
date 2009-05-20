namespace ###PROXY_NAMESPACE###;

/**
 * AOP Proxy for the class "###TARGET_CLASS_NAME###".
 *
###CLASS_ANNOTATIONS### */
class ###PROXY_CLASS_NAME### extends \###TARGET_CLASS_NAME### implements ###INTRODUCED_INTERFACES###\F3\FLOW3\AOP\ProxyInterface {

	/**
	 * An array of target method names and their advices grouped by advice type
	 * @var array
	 */
	protected $targetMethodsAndGroupedAdvices = array();

	/**
	 * An array of method names and the advices grouped by advice type in the order of their invocation
	 * @var array
	 */
	protected $groupedAdviceChains = array();

	/**
	 * An array of method names and their state: If set to TRUE, an advice for that method is currently being executed
	 * @var array
	 */
	protected $methodIsInAdviceMode = array();

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;
###METHODS_INTERCEPTOR_CODE###
	/**
	 * Declares methods and advices
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function FLOW3_AOP_Proxy_declareMethodsAndAdvices() {
###METHODS_AND_ADVICES_ARRAY_CODE###
	}

	/**
	 * Invokes the joinpoint - calls the target methods.
	 *
	 * @param \F3\FLOW3\AOP\JoinPointInterface: The join point
	 * @return mixed Result of the target (ie. original) method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		if (isset($this->methodIsInAdviceMode[$joinPoint->getMethodName()])) {
			return call_user_func_array(array($this, $joinPoint->getMethodName()), $joinPoint->getMethodArguments());
		}
	}

	/**
	 * Returns the name of the class this proxy extends.
	 *
	 * @return string Name of the target class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function FLOW3_AOP_Proxy_getProxyTargetClassName() {
		return '###TARGET_CLASS_NAME###';
	}

	/**
	 * Returns the value of an arbitrary property.
	 * The method does not have to check if the property exists.
	 *
	 * @param string $propertyName Name of the property
	 * @return mixed Value of the property
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function FLOW3_AOP_Proxy_getProperty($propertyName) {
		return $this->$propertyName;
	}

	/**
	 * Sets the value of an arbitrary property.
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $propertyValue Value to set
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function FLOW3_AOP_Proxy_setProperty($propertyName, $propertyValue) {
		$this->$propertyName = $propertyValue;
	}

	/**
	 * Returns the advice chains (if any) grouped by advice type for a join point.
	 * Advice chains are only used in combination with Around advices.
	 *
	 * @param string $methodName: Method to return the advice chains for
	 * @return mixed The advice chains  (array of \F3\FLOW3\AOP\Advice\AdviceChain) or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function FLOW3_AOP_Proxy_getAdviceChains($methodName) {
		$adviceChains = NULL;
		if (is_array($this->groupedAdviceChains)) {
			if (isset($this->groupedAdviceChains[$methodName])) {
				$adviceChains = $this->groupedAdviceChains[$methodName];
			} else {
				if (isset($this->targetMethodsAndGroupedAdvices[$methodName])) {
					$groupedAdvices = $this->targetMethodsAndGroupedAdvices[$methodName];
					if (isset($groupedAdvices['F3\FLOW3\AOP\Advice\AroundAdvice'])) {
						$this->groupedAdviceChains[$methodName]['F3\FLOW3\AOP\Advice\AroundAdvice'] = new \F3\FLOW3\AOP\Advice\AdviceChain($groupedAdvices['F3\FLOW3\AOP\Advice\AroundAdvice'], $this);
						$adviceChains = $this->groupedAdviceChains[$methodName];
					}
				}
			}
		}
		return $adviceChains;
	}
}
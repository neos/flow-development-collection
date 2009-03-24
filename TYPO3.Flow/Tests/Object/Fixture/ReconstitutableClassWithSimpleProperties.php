<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Object\Fixture;

class ReconstitutableClassWithSimpleProperties implements \F3\FLOW3\AOP\ProxyInterface {

	/**
	 * @var string
	 */
	protected $firstProperty;

	/**
	 * @var mixed
	 */
	protected $secondProperty;

	/**
	 * @var mixed
	 */
	public $publicProperty;

	/**
	 * @var boolean
	 */
	public $constructorHasBeenCalled = FALSE;

	/**
	 * @var string
	 */
	protected $stringDependency;

	/**
	 * The constructor - similar to what you would find in a AOP proxy class.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($someArgument, \F3\FLOW3\Object\FactoryInterface $FLOW3_AOP_Proxy_objectFactory) {
		$this->constructorHasBeenCalled = TRUE;
	}

	public function injectStringDependency($string) {
		$this->stringDependency = $string;
	}

	public function FLOW3_AOP_Proxy_getProxyTargetClassName() {
		return 'F3\FLOW3\Tests\Object\Fixture\ReconstitutableClassWithSimpleProperties';
	}

	public function FLOW3_AOP_Proxy_getProperty($propertyName) {
		return $this->$propertyName;
	}

	public function FLOW3_AOP_Proxy_setProperty($propertyName, $propertyValue) {
		$this->$propertyName = $propertyValue;
	}

	public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}

	public function FLOW3_AOP_Proxy_declareMethodsAndAdvices() {}

}
?>
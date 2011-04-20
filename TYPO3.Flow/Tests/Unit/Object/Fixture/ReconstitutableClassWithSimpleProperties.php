<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Object\Fixture;

class ReconstitutableClassWithSimpleProperties implements \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface, \F3\FLOW3\Object\Proxy\ProxyInterface {

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
	public function __construct($someArgument, \F3\FLOW3\Object\ObjectManagerInterface $FLOW3_AOP_Proxy_objectManager) {
		$this->constructorHasBeenCalled = TRUE;
	}

	public function injectStringDependency($string) {
		$this->stringDependency = $string;
	}

	public function FLOW3_AOP_Proxy_invokeJoinPoint(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {}

	public function FLOW3_AOP_Proxy_declareMethodsAndAdvices() {}

}
?>
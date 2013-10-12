<?php
namespace TYPO3\Flow\Tests\Object\Fixture;

class ReconstitutableClassWithSimpleProperties implements \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface, \TYPO3\Flow\Object\Proxy\ProxyInterface {

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
	 */
	public function __construct($someArgument, \TYPO3\Flow\Object\ObjectManagerInterface $Flow_Aop_Proxy_objectManager) {
		$this->constructorHasBeenCalled = TRUE;
	}

	public function injectStringDependency($string) {
		$this->stringDependency = $string;
	}

	public function Flow_Aop_Proxy_invokeJoinPoint(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {}

	public function Flow_Aop_Proxy_declareMethodsAndAdvices() {}

}

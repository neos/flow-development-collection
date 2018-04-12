<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\Fixture;

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Persistence\Aspect\PersistenceMagicInterface;

class ReconstitutableClassWithSimpleProperties implements PersistenceMagicInterface, ProxyInterface
{
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
    public $constructorHasBeenCalled = false;

    /**
     * @var string
     */
    protected $stringDependency;

    /**
     * The constructor - similar to what you would find in a AOP proxy class.
     *
     */
    public function __construct($someArgument, ObjectManagerInterface $Flow_Aop_Proxy_objectManager)
    {
        $this->constructorHasBeenCalled = true;
    }

    public function injectStringDependency($string)
    {
        $this->stringDependency = $string;
    }

    public function Flow_Aop_Proxy_invokeJoinPoint(JoinPointInterface $joinPoint)
    {
    }

    public function Flow_Aop_Proxy_declareMethodsAndAdvices()
    {
    }
}

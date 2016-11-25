<?php
namespace Neos\Flow\Tests\Unit\Mvc;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Http;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Exception\InvalidHashException;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC ActionRequest class
 */
class ActionRequestTest extends UnitTestCase
{
    /**
     * @var ActionRequest
     */
    protected $actionRequest;

    /**
     * @var Http\Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    public function setUp()
    {
        $this->mockHttpRequest = $this->getMockBuilder(Http\Request::class)->disableOriginalConstructor()->getMock();
        $this->actionRequest = new ActionRequest($this->mockHttpRequest);
    }

    /**
     * By design, the root request will always be an HTTP request because it is
     * the only of the two types which can be instantiated without having to pass
     * another request as the parent request.
     *
     * @test
     */
    public function anHttpRequestOrActionRequestIsRequiredAsParentRequest()
    {
        $this->assertSame($this->mockHttpRequest, $this->actionRequest->getParentRequest());

        $anotherActionRequest = new ActionRequest($this->actionRequest);
        $this->assertSame($this->actionRequest, $anotherActionRequest->getParentRequest());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @test
     */
    public function constructorThrowsAnExceptionIfNoValidRequestIsPassed()
    {
        new ActionRequest(new \stdClass());
    }

    /**
     * @test
     */
    public function getHttpRequestReturnsTheHttpRequestWhichIsTheRootOfAllActionRequests()
    {
        $anotherActionRequest = new ActionRequest($this->actionRequest);
        $yetAnotherActionRequest = new ActionRequest($anotherActionRequest);

        $this->assertSame($this->mockHttpRequest, $this->actionRequest->getHttpRequest());
        $this->assertSame($this->mockHttpRequest, $yetAnotherActionRequest->getHttpRequest());
        $this->assertSame($this->mockHttpRequest, $anotherActionRequest->getHttpRequest());
    }

    /**
     * @test
     */
    public function getMainRequestReturnsTheTopLevelActionRequestWhoseParentIsTheHttpRequest()
    {
        $anotherActionRequest = new ActionRequest($this->actionRequest);
        $yetAnotherActionRequest = new ActionRequest($anotherActionRequest);

        $this->assertSame($this->actionRequest, $this->actionRequest->getMainRequest());
        $this->assertSame($this->actionRequest, $yetAnotherActionRequest->getMainRequest());
        $this->assertSame($this->actionRequest, $anotherActionRequest->getMainRequest());
    }

    /**
     * @test
     */
    public function isMainRequestChecksIfTheParentRequestIsNotAnHttpRequest()
    {
        $anotherActionRequest = new ActionRequest($this->actionRequest);
        $yetAnotherActionRequest = new ActionRequest($anotherActionRequest);

        $this->assertTrue($this->actionRequest->isMainRequest());
        $this->assertFalse($anotherActionRequest->isMainRequest());
        $this->assertFalse($yetAnotherActionRequest->isMainRequest());
    }

    /**
     * @test
     */
    public function requestIsDispatchable()
    {
        $mockDispatcher = $this->createMock(Dispatcher::class);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockDispatcher));
        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

        $this->assertFalse($this->actionRequest->isDispatched());
        $this->actionRequest->setDispatched(true);
        $this->assertTrue($this->actionRequest->isDispatched());
        $this->actionRequest->setDispatched(false);
        $this->assertFalse($this->actionRequest->isDispatched());
    }

    /**
     * @test
     */
    public function getControllerObjectNameReturnsObjectNameDerivedFromPreviouslySetControllerInformation()
    {
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->expects($this->any())->method('getCaseSensitivePackageKey')->with('somepackage')->will($this->returnValue('SomePackage'));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('SomePackage\Some\Subpackage\Controller\SomeControllerController')
            ->will($this->returnValue('SomePackage\Some\SubPackage\Controller\SomeControllerController'));

        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);
        $this->inject($this->actionRequest, 'packageManager', $mockPackageManager);

        $this->actionRequest->setControllerPackageKey('somepackage');
        $this->actionRequest->setControllerSubPackageKey('Some\Subpackage');
        $this->actionRequest->setControllerName('SomeController');

        $this->assertEquals('SomePackage\Some\SubPackage\Controller\SomeControllerController', $this->actionRequest->getControllerObjectName());
    }

    /**
     * @test
     */
    public function getControllerObjectNameReturnsAnEmptyStringIfTheResolvedControllerDoesNotExist()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->at(0))->method('getCaseSensitiveObjectName')->with('SomePackage\Some\Subpackage\Controller\SomeControllerController')
            ->will($this->returnValue(false));

        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->expects($this->any())->method('getCaseSensitivePackageKey')->with('somepackage')->will($this->returnValue('SomePackage'));

        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);
        $this->inject($this->actionRequest, 'packageManager', $mockPackageManager);

        $this->actionRequest->setControllerPackageKey('somepackage');
        $this->actionRequest->setControllerSubPackageKey('Some\Subpackage');
        $this->actionRequest->setControllerName('SomeController');

        $this->assertEquals('', $this->actionRequest->getControllerObjectName());
    }

    /**
     * Data Provider
     */
    public function caseSensitiveObjectNames()
    {
        return [
            [
                'Neos\Foo\Controller\BarController',
                [
                    'controllerPackageKey' => 'Neos.Foo',
                    'controllerSubpackageKey' => '',
                    'controllerName' => 'Bar',
                ]
            ],
            [
                'Neos\Foo\Bar\Controller\BazController',
                [
                    'controllerPackageKey' => 'Neos.Foo',
                    'controllerSubpackageKey' => 'Bar',
                    'controllerName' => 'Baz',
                ]
            ],
            [
                'Neos\Foo\Bar\Bla\Controller\Baz\QuuxController',
                [
                    'controllerPackageKey' => 'Neos.Foo',
                    'controllerSubpackageKey' => 'Bar\Bla',
                    'controllerName' => 'Baz\Quux',
                ]
            ],
            [
                'Neos\Foo\Controller\Bar\BazController',
                [
                    'controllerPackageKey' => 'Neos.Foo',
                    'controllerSubpackageKey' => '',
                    'controllerName' => 'Bar\Baz',
                ]
            ],
            [
                'Neos\Foo\Controller\Bar\Baz\QuuxController',
                [
                    'controllerPackageKey' => 'Neos.Foo',
                    'controllerSubpackageKey' => '',
                    'controllerName' => 'Bar\Baz\Quux',
                ]
            ]
        ];
    }

    /**
     * @test
     * @param string $objectName
     * @param array $parts
     * @dataProvider caseSensitiveObjectNames
     */
    public function setControllerObjectNameSplitsTheGivenObjectNameIntoItsParts($objectName, array $parts)
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->with($objectName)->will($this->returnValue($objectName));
        $mockObjectManager->expects($this->any())->method('getPackageKeyByObjectName')->with($objectName)->will($this->returnValue($parts['controllerPackageKey']));

        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

        $this->actionRequest->setControllerObjectName($objectName);
        $this->assertSame($parts['controllerPackageKey'], $this->actionRequest->getControllerPackageKey());
        $this->assertSame($parts['controllerSubpackageKey'], $this->actionRequest->getControllerSubpackageKey());
        $this->assertSame($parts['controllerName'], $this->actionRequest->getControllerName());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\ObjectManagement\Exception\UnknownObjectException
     */
    public function setControllerObjectNameThrowsExceptionOnUnknownObjectName()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('getCaseSensitiveObjectName')->will($this->returnValue(false));

        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

        $this->actionRequest->setControllerObjectName('SomeUnknownControllerObjectName');
    }

    /**
     * @test
     */
    public function getControllerNameExtractsTheControllerNameFromTheControllerObjectNameToAssureTheCorrectCase()
    {
        /** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('Neos\MyPackage\Controller\Foo\BarController'));

        $actionRequest->setControllerName('foo\bar');
        $this->assertEquals('Foo\Bar', $actionRequest->getControllerName());
    }

    /**
     * @test
     */
    public function getControllerNameReturnsTheUnknownCasesControllerNameIfNoControllerObjectNameCouldBeDetermined()
    {
        /** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue(''));

        $actionRequest->setControllerName('foo\bar');
        $this->assertEquals('foo\bar', $actionRequest->getControllerName());
    }

    /**
     * @test
     */
    public function getControllerSubpackageKeyExtractsTheSubpackageKeyFromTheControllerObjectNameToAssureTheCorrectCase()
    {
        /** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('Neos\MyPackage\Some\SubPackage\Controller\Foo\BarController'));

        /** @var PackageManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockPackageManager */
        $mockPackageManager = $this->createMock(PackageManagerInterface::class);
        $mockPackageManager->expects($this->any())->method('getCaseSensitivePackageKey')->with('neos.mypackage')->will($this->returnValue('Neos.MyPackage'));
        $this->inject($actionRequest, 'packageManager', $mockPackageManager);

        $actionRequest->setControllerPackageKey('neos.mypackage');
        $actionRequest->setControllerSubpackageKey('some\subpackage');
        $this->assertEquals('Some\SubPackage', $actionRequest->getControllerSubpackageKey());
    }

    /**
     * @test
     */
    public function getControllerSubpackageKeyReturnsNullIfNoSubpackageKeyIsSet()
    {
        /** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('Neos\MyPackage\Controller\Foo\BarController'));

        /** @var PackageManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockPackageManager */
        $mockPackageManager = $this->createMock(PackageManagerInterface::class);
        $mockPackageManager->expects($this->any())->method('getCaseSensitivePackageKey')->with('neos.mypackage')->will($this->returnValue('Neos.MyPackage'));
        $this->inject($actionRequest, 'packageManager', $mockPackageManager);

        $actionRequest->setControllerPackageKey('neos.mypackage');
        $this->assertNull($actionRequest->getControllerSubpackageKey());
    }

    /**
     * @test
     */
    public function getControllerSubpackageKeyReturnsTheUnknownCasesPackageKeyIfNoControllerObjectNameCouldBeDetermined()
    {
        /** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue(''));

        /** @var PackageManagerInterface|\PHPUnit_Framework_MockObject_MockObject $mockPackageManager */
        $mockPackageManager = $this->createMock(PackageManagerInterface::class);
        $mockPackageManager->expects($this->any())->method('getCaseSensitivePackageKey')->with('neos.mypackage')->will($this->returnValue(false));
        $this->inject($actionRequest, 'packageManager', $mockPackageManager);

        $actionRequest->setControllerPackageKey('neos.mypackage');
        $actionRequest->setControllerSubpackageKey('some\subpackage');
        $this->assertEquals('some\subpackage', $actionRequest->getControllerSubpackageKey());
    }

    /**
     * Data Provider
     */
    public function invalidControllerNames()
    {
        return [
            [42],
            [false],
            ['foo_bar_baz'],
        ];
    }

    /**
     * @test
     * @param mixed $invalidControllerName
     * @dataProvider invalidControllerNames
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidControllerNameException
     */
    public function setControllerNameThrowsExceptionOnInvalidControllerNames($invalidControllerName)
    {
        $this->actionRequest->setControllerName($invalidControllerName);
    }

    /**
     * @test
     */
    public function theActionNameCanBeSetAndRetrieved()
    {
        /** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue(''));

        $actionRequest->setControllerActionName('theAction');
        $this->assertEquals('theAction', $actionRequest->getControllerActionName());
    }

    /**
     * Data Provider
     */
    public function invalidActionNames()
    {
        return [
            [42],
            [''],
            ['FooBar'],
        ];
    }

    /**
     * @test
     * @param mixed $invalidActionName
     * @dataProvider invalidActionNames
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidActionNameException
     */
    public function setControllerActionNameThrowsExceptionOnInvalidActionNames($invalidActionName)
    {
        $this->actionRequest->setControllerActionName($invalidActionName);
    }

    /**
     * @test
     */
    public function theActionNamesCaseIsFixedIfItIsAllLowerCaseAndTheControllerObjectNameIsKnown()
    {
        $mockControllerClassName = 'Mock' . md5(uniqid(mt_rand(), true));
        eval('
			class ' . $mockControllerClassName . ' extends \Neos\Flow\Mvc\Controller\ActionController {
				public function someGreatAction() {}
			}
		');

        $mockController = $this->createMock($mockControllerClassName, ['someGreatAction'], [], '', false);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')
            ->with('Neos\Flow\MyControllerObjectName')
            ->will($this->returnValue(get_class($mockController)));

        /** @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject $actionRequest */
        $actionRequest = $this->getAccessibleMock(ActionRequest::class, ['getControllerObjectName'], [], '', false);
        $actionRequest->expects($this->once())->method('getControllerObjectName')->will($this->returnValue('Neos\Flow\MyControllerObjectName'));
        $actionRequest->_set('objectManager', $mockObjectManager);

        $actionRequest->setControllerActionName('somegreat');
        $this->assertEquals('someGreat', $actionRequest->getControllerActionName());
    }

    /**
     * @test
     */
    public function aSingleArgumentCanBeSetWithSetArgumentAndRetrievedWithGetArgument()
    {
        $this->actionRequest->setArgument('someArgumentName', 'theValue');
        $this->assertEquals('theValue', $this->actionRequest->getArgument('someArgumentName'));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidArgumentNameException
     */
    public function setArgumentThrowsAnExceptionOnInvalidArgumentNames()
    {
        $this->actionRequest->setArgument('', 'theValue');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidArgumentTypeException
     */
    public function setArgumentDoesNotAllowObjectValuesForRegularArguments()
    {
        $this->actionRequest->setArgument('foo', new \stdClass());
    }

    /**
     * @test
     */
    public function allArgumentsCanBeSetOrRetrievedAtOnce()
    {
        $arguments = [
            'foo' => 'fooValue',
            'bar' => 'barValue'
        ];

        $this->actionRequest->setArguments($arguments);
        $this->assertEquals($arguments, $this->actionRequest->getArguments());
    }

    /**
     * @test
     */
    public function internalArgumentsAreHandledSeparately()
    {
        $this->actionRequest->setArgument('__someInternalArgument', 'theValue');

        $this->assertFalse($this->actionRequest->hasArgument('__someInternalArgument'));
        $this->assertEquals('theValue', $this->actionRequest->getInternalArgument('__someInternalArgument'));
        $this->assertEquals(['__someInternalArgument' => 'theValue'], $this->actionRequest->getInternalArguments());
    }

    /**
     * @test
     */
    public function internalArgumentsMayHaveObjectValues()
    {
        $someObject = new \stdClass();

        $this->actionRequest->setArgument('__someInternalArgument', $someObject);

        $this->assertSame($someObject, $this->actionRequest->getInternalArgument('__someInternalArgument'));
    }

    /**
     * @test
     */
    public function pluginArgumentsAreHandledSeparately()
    {
        $this->actionRequest->setArgument('--typo3-flow-foo-viewhelper-paginate', ['@controller' => 'Foo', 'page' => 5]);

        $this->assertFalse($this->actionRequest->hasArgument('--typo3-flow-foo-viewhelper-paginate'));
        $this->assertEquals(['typo3-flow-foo-viewhelper-paginate' => ['@controller' => 'Foo', 'page' => 5]], $this->actionRequest->getPluginArguments());
    }

    /**
     * @test
     */
    public function argumentNamespaceCanBeSpecified()
    {
        $this->assertSame('', $this->actionRequest->getArgumentNamespace());
        $this->actionRequest->setArgumentNamespace('someArgumentNamespace');
        $this->assertSame('someArgumentNamespace', $this->actionRequest->getArgumentNamespace());
    }

    /**
     * @test
     */
    public function theRepresentationFormatCanBeSetAndRetrieved()
    {
        $this->actionRequest->setFormat('html');
        $this->assertEquals('html', $this->actionRequest->getFormat());

        $this->actionRequest->setFormat('doc');
        $this->assertEquals('doc', $this->actionRequest->getFormat());

        $this->actionRequest->setFormat('hTmL');
        $this->assertEquals('html', $this->actionRequest->getFormat());
    }

    /**
     * @test
     */
    public function cloneResetsTheStatusToNotDispatched()
    {
        $this->actionRequest->setDispatched(true);
        $cloneRequest = clone $this->actionRequest;

        $this->assertTrue($this->actionRequest->isDispatched());
        $this->assertFalse($cloneRequest->isDispatched());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Security\Exception\InvalidHashException
     */
    public function getReferringRequestThrowsAnExceptionIfTheHmacOfTheArgumentsCouldNotBeValid()
    {
        $serializedArguments = base64_encode('some manipulated arguments string without valid HMAC');
        $referrer = [
            '@controller' => 'Foo',
            '@action' => 'bar',
            'arguments' => $serializedArguments
        ];

        $mockHashService = $this->getMockBuilder(HashService::class)->getMock();
        $mockHashService->expects($this->once())->method('validateAndStripHmac')->with($serializedArguments)->will($this->throwException(new InvalidHashException()));
        $this->inject($this->actionRequest, 'hashService', $mockHashService);

        $this->actionRequest->setArgument('__referrer', $referrer);

        $this->actionRequest->getReferringRequest();
    }

    /**
     * @test
     */
    public function setDispatchedEmitsSignalIfDispatched()
    {
        $mockDispatcher = $this->createMock(Dispatcher::class);
        $mockDispatcher->expects($this->once())->method('dispatch')->with(ActionRequest::class, 'requestDispatched', [$this->actionRequest]);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockDispatcher));
        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

        $this->actionRequest->setDispatched(true);
    }

    /**
     * @test
     */
    public function setControllerPackageKeyWithLowercasePackageKeyResolvesCorrectly()
    {
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->expects($this->any())->method('getCaseSensitivePackageKey')->with('acme.testpackage')->will($this->returnValue('Acme.Testpackage'));

        $this->inject($this->actionRequest, 'packageManager', $mockPackageManager);
        $this->actionRequest->setControllerPackageKey('acme.testpackage');

        $this->assertEquals('Acme.Testpackage', $this->actionRequest->getControllerPackageKey());
    }

    /**
     * @test
     */
    public function internalArgumentsOfActionRequestOverruleThoseOfTheHttpRequest()
    {
        $this->actionRequest->setArguments(['__internalArgument' => 'action request']);

        $expectedResult = ['__internalArgument' => 'action request'];
        $this->assertSame($expectedResult, $this->actionRequest->getInternalArguments());
    }

    /**
     * @test
     */
    public function pluginArgumentsOfActionRequestOverruleThoseOfTheHttpRequest()
    {
        $this->actionRequest->setArguments(['--pluginArgument' => 'action request']);

        $expectedResult = ['pluginArgument' => 'action request'];
        $this->assertSame($expectedResult, $this->actionRequest->getPluginArguments());
    }
}

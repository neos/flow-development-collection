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
use Neos\Flow\Mvc\Exception\InvalidActionNameException;
use Neos\Flow\Mvc\Exception\InvalidArgumentNameException;
use Neos\Flow\Mvc\Exception\InvalidArgumentTypeException;
use Neos\Flow\Mvc\Exception\InvalidControllerNameException;
use Neos\Flow\ObjectManagement\Exception\UnknownObjectException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Security\Cryptography\HashService;
use Neos\Flow\Security\Exception\InvalidHashException;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ServerRequestInterface;

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
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    protected function setUp(): void
    {
        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->actionRequest = ActionRequest::fromHttpRequest($this->mockHttpRequest);
    }

    /**
     * By design, the root request will always be an HTTP request because it is
     * the only of the two types which can be instantiated without having to pass
     * another request as the parent request.
     *
     * @test
     */
    public function anActionRequestIsRequiredAsParentRequest()
    {
        self::assertSame(null, $this->actionRequest->getParentRequest());

        $anotherActionRequest = $this->actionRequest->createSubRequest();
        self::assertSame($this->actionRequest, $anotherActionRequest->getParentRequest());
    }

    /**
     * @test
     */
    public function constructorThrowsAnExceptionIfNoValidRequestIsPassed()
    {
        $this->expectException(\Error::class);
        new ActionRequest(new \stdClass());
    }

    /**
     * @test
     */
    public function getHttpRequestReturnsTheHttpRequestWhichIsTheRootOfAllActionRequests()
    {
        $anotherActionRequest = $this->actionRequest->createSubRequest();
        $yetAnotherActionRequest = $anotherActionRequest->createSubRequest();

        self::assertSame($this->mockHttpRequest, $this->actionRequest->getHttpRequest());
        self::assertSame($this->mockHttpRequest, $yetAnotherActionRequest->getHttpRequest());
        self::assertSame($this->mockHttpRequest, $anotherActionRequest->getHttpRequest());
    }

    /**
     * @test
     */
    public function getMainRequestReturnsTheTopLevelActionRequestWhoseParentIsTheHttpRequest()
    {
        $anotherActionRequest = $this->actionRequest->createSubRequest();
        $yetAnotherActionRequest = $anotherActionRequest->createSubRequest();

        self::assertSame($this->actionRequest, $this->actionRequest->getMainRequest());
        self::assertSame($this->actionRequest, $yetAnotherActionRequest->getMainRequest());
        self::assertSame($this->actionRequest, $anotherActionRequest->getMainRequest());
    }

    /**
     * @test
     */
    public function isMainRequestChecksIfTheParentRequestIsNotAnHttpRequest()
    {
        $anotherActionRequest = $this->actionRequest->createSubRequest();
        $yetAnotherActionRequest = $anotherActionRequest->createSubRequest();

        self::assertTrue($this->actionRequest->isMainRequest());
        self::assertFalse($anotherActionRequest->isMainRequest());
        self::assertFalse($yetAnotherActionRequest->isMainRequest());
    }

    /**
     * @test
     */
    public function requestIsDispatchable()
    {
        $mockDispatcher = $this->createMock(Dispatcher::class);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->will(self::returnValue($mockDispatcher));
        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

        self::assertFalse($this->actionRequest->isDispatched());
        $this->actionRequest->setDispatched(true);
        self::assertTrue($this->actionRequest->isDispatched());
        $this->actionRequest->setDispatched(false);
        self::assertFalse($this->actionRequest->isDispatched());
    }

    /**
     * @test
     */
    public function getControllerObjectNameReturnsObjectNameDerivedFromPreviouslySetControllerInformation()
    {
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->expects(self::any())->method('getCaseSensitivePackageKey')->with('somepackage')->will(self::returnValue('SomePackage'));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('getCaseSensitiveObjectName')->with('SomePackage\Some\Subpackage\Controller\SomeControllerController')
            ->will(self::returnValue('SomePackage\Some\SubPackage\Controller\SomeControllerController'));

        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);
        $this->inject($this->actionRequest, 'packageManager', $mockPackageManager);

        $this->actionRequest->setControllerPackageKey('somepackage');
        $this->actionRequest->setControllerSubPackageKey('Some\Subpackage');
        $this->actionRequest->setControllerName('SomeController');

        self::assertEquals('SomePackage\Some\SubPackage\Controller\SomeControllerController', $this->actionRequest->getControllerObjectName());
    }

    /**
     * @test
     */
    public function getControllerObjectNameReturnsAnEmptyStringIfTheResolvedControllerDoesNotExist()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('getCaseSensitiveObjectName')->with('SomePackage\Some\Subpackage\Controller\SomeControllerController')
            ->will(self::returnValue(null));

        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->expects(self::any())->method('getCaseSensitivePackageKey')->with('somepackage')->will(self::returnValue('SomePackage'));

        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);
        $this->inject($this->actionRequest, 'packageManager', $mockPackageManager);

        $this->actionRequest->setControllerPackageKey('somepackage');
        $this->actionRequest->setControllerSubPackageKey('Some\Subpackage');
        $this->actionRequest->setControllerName('SomeController');

        self::assertEquals('', $this->actionRequest->getControllerObjectName());
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
        $mockObjectManager->expects(self::any())->method('getCaseSensitiveObjectName')->with($objectName)->will(self::returnValue($objectName));
        $mockObjectManager->expects(self::any())->method('getPackageKeyByObjectName')->with($objectName)->will(self::returnValue($parts['controllerPackageKey']));

        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

        $this->actionRequest->setControllerObjectName($objectName);
        self::assertSame($parts['controllerPackageKey'], $this->actionRequest->getControllerPackageKey());
        self::assertSame($parts['controllerSubpackageKey'], $this->actionRequest->getControllerSubpackageKey());
        self::assertSame($parts['controllerName'], $this->actionRequest->getControllerName());
    }

    /**
     * @test
     */
    public function setControllerObjectNameThrowsExceptionOnUnknownObjectName()
    {
        $this->expectException(UnknownObjectException::class);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('getCaseSensitiveObjectName')->will(self::returnValue(null));

        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

        $this->actionRequest->setControllerObjectName('SomeUnknownControllerObjectName');
    }

    /**
     * @test
     */
    public function getControllerNameExtractsTheControllerNameFromTheControllerObjectNameToAssureTheCorrectCase()
    {
        /** @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects(self::once())->method('getControllerObjectName')->will(self::returnValue('Neos\MyPackage\Controller\Foo\BarController'));

        $actionRequest->setControllerName('foo\bar');
        self::assertEquals('Foo\Bar', $actionRequest->getControllerName());
    }

    /**
     * @test
     */
    public function getControllerNameReturnsTheUnknownCasesControllerNameIfNoControllerObjectNameCouldBeDetermined()
    {
        /** @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects(self::once())->method('getControllerObjectName')->will(self::returnValue(''));

        $actionRequest->setControllerName('foo\bar');
        self::assertEquals('foo\bar', $actionRequest->getControllerName());
    }

    /**
     * @test
     */
    public function getControllerSubpackageKeyExtractsTheSubpackageKeyFromTheControllerObjectNameToAssureTheCorrectCase()
    {
        /** @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects(self::once())->method('getControllerObjectName')->will(self::returnValue('Neos\MyPackage\Some\SubPackage\Controller\Foo\BarController'));

        /** @var PackageManager|\PHPUnit\Framework\MockObject\MockObject $mockPackageManager */
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->expects(self::any())->method('getCaseSensitivePackageKey')->with('neos.mypackage')->will(self::returnValue('Neos.MyPackage'));
        $this->inject($actionRequest, 'packageManager', $mockPackageManager);

        $actionRequest->setControllerPackageKey('neos.mypackage');
        $actionRequest->setControllerSubpackageKey('some\subpackage');
        self::assertEquals('Some\SubPackage', $actionRequest->getControllerSubpackageKey());
    }

    /**
     * @test
     */
    public function getControllerSubpackageKeyReturnsNullIfNoSubpackageKeyIsSet()
    {
        /** @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects(self::any())->method('getControllerObjectName')->will(self::returnValue('Neos\MyPackage\Controller\Foo\BarController'));

        /** @var PackageManager|\PHPUnit\Framework\MockObject\MockObject $mockPackageManager */
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->expects(self::any())->method('getCaseSensitivePackageKey')->with('neos.mypackage')->will(self::returnValue('Neos.MyPackage'));
        $this->inject($actionRequest, 'packageManager', $mockPackageManager);

        $actionRequest->setControllerPackageKey('neos.mypackage');
        self::assertNull($actionRequest->getControllerSubpackageKey());
    }

    /**
     * @test
     */
    public function getControllerSubpackageKeyReturnsTheUnknownCasesPackageKeyIfNoControllerObjectNameCouldBeDetermined()
    {
        /** @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects(self::once())->method('getControllerObjectName')->will(self::returnValue(''));

        /** @var PackageManager|\PHPUnit\Framework\MockObject\MockObject $mockPackageManager */
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->expects(self::any())->method('getCaseSensitivePackageKey')->with('neos.mypackage')->will(self::returnValue(false));
        $this->inject($actionRequest, 'packageManager', $mockPackageManager);

        $actionRequest->setControllerPackageKey('neos.mypackage');
        $actionRequest->setControllerSubpackageKey('some\subpackage');
        self::assertEquals('some\subpackage', $actionRequest->getControllerSubpackageKey());
    }

    /**
     * Data Provider
     */
    public function invalidControllerNames()
    {
        return [
            //[42],
            //[false],
            ['foo_bar_baz'],
        ];
    }

    /**
     * @test
     * @param mixed $invalidControllerName
     * @dataProvider invalidControllerNames
     */
    public function setControllerNameThrowsExceptionOnInvalidControllerNames($invalidControllerName)
    {
        $this->expectException(InvalidControllerNameException::class);
        $this->actionRequest->setControllerName($invalidControllerName);
    }

    /**
     * @test
     */
    public function theActionNameCanBeSetAndRetrieved()
    {
        /** @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->setMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects(self::once())->method('getControllerObjectName')->will(self::returnValue(''));

        $actionRequest->setControllerActionName('theAction');
        self::assertEquals('theAction', $actionRequest->getControllerActionName());
    }

    /**
     * Data Provider
     */
    public function invalidActionNames()
    {
        return [
            //[42],
            [''],
            ['FooBar'],
        ];
    }

    /**
     * @test
     * @param mixed $invalidActionName
     * @dataProvider invalidActionNames
     */
    public function setControllerActionNameThrowsExceptionOnInvalidActionNames($invalidActionName)
    {
        $this->expectException(InvalidActionNameException::class);
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
        $mockObjectManager->expects(self::once())->method('getClassNameByObjectName')
            ->with('Neos\Flow\MyControllerObjectName')
            ->will(self::returnValue(get_class($mockController)));

        /** @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject $actionRequest */
        $actionRequest = $this->getAccessibleMock(ActionRequest::class, ['getControllerObjectName'], [], '', false);
        $actionRequest->expects(self::once())->method('getControllerObjectName')->will(self::returnValue('Neos\Flow\MyControllerObjectName'));
        $actionRequest->_set('objectManager', $mockObjectManager);

        $actionRequest->setControllerActionName('somegreat');
        self::assertEquals('someGreat', $actionRequest->getControllerActionName());
    }

    /**
     * @test
     */
    public function aSingleArgumentCanBeSetWithSetArgumentAndRetrievedWithGetArgument()
    {
        $this->actionRequest->setArgument('someArgumentName', 'theValue');
        self::assertEquals('theValue', $this->actionRequest->getArgument('someArgumentName'));
    }

    /**
     * @test
     */
    public function setArgumentThrowsAnExceptionOnInvalidArgumentNames()
    {
        $this->expectException(InvalidArgumentNameException::class);
        $this->actionRequest->setArgument('', 'theValue');
    }

    /**
     * @test
     */
    public function setArgumentDoesNotAllowObjectValuesForRegularArguments()
    {
        $this->expectException(InvalidArgumentTypeException::class);
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
        self::assertEquals($arguments, $this->actionRequest->getArguments());
    }

    /**
     * @test
     */
    public function internalArgumentsAreHandledSeparately()
    {
        $this->actionRequest->setArgument('__someInternalArgument', 'theValue');

        self::assertFalse($this->actionRequest->hasArgument('__someInternalArgument'));
        self::assertEquals('theValue', $this->actionRequest->getInternalArgument('__someInternalArgument'));
        self::assertEquals(['__someInternalArgument' => 'theValue'], $this->actionRequest->getInternalArguments());
    }

    /**
     * @test
     */
    public function internalArgumentsMayHaveObjectValues()
    {
        $someObject = new \stdClass();

        $this->actionRequest->setArgument('__someInternalArgument', $someObject);

        self::assertSame($someObject, $this->actionRequest->getInternalArgument('__someInternalArgument'));
    }

    /**
     * @test
     */
    public function pluginArgumentsAreHandledSeparately()
    {
        $this->actionRequest->setArgument('--typo3-flow-foo-viewhelper-paginate', ['@controller' => 'Foo', 'page' => 5]);

        self::assertFalse($this->actionRequest->hasArgument('--typo3-flow-foo-viewhelper-paginate'));
        self::assertEquals(['typo3-flow-foo-viewhelper-paginate' => ['@controller' => 'Foo', 'page' => 5]], $this->actionRequest->getPluginArguments());
    }

    /**
     * @test
     */
    public function argumentNamespaceCanBeSpecified()
    {
        self::assertSame('', $this->actionRequest->getArgumentNamespace());
        $this->actionRequest->setArgumentNamespace('someArgumentNamespace');
        self::assertSame('someArgumentNamespace', $this->actionRequest->getArgumentNamespace());
    }

    /**
     * @test
     */
    public function theRepresentationFormatCanBeSetAndRetrieved()
    {
        $this->actionRequest->setFormat('html');
        self::assertEquals('html', $this->actionRequest->getFormat());

        $this->actionRequest->setFormat('doc');
        self::assertEquals('doc', $this->actionRequest->getFormat());

        $this->actionRequest->setFormat('hTmL');
        self::assertEquals('html', $this->actionRequest->getFormat());
    }

    /**
     * @test
     */
    public function cloneResetsTheStatusToNotDispatched()
    {
        $this->actionRequest->setDispatched(true);
        $cloneRequest = clone $this->actionRequest;

        self::assertTrue($this->actionRequest->isDispatched());
        self::assertFalse($cloneRequest->isDispatched());
    }

    /**
     * @test
     */
    public function getReferringRequestThrowsAnExceptionIfTheHmacOfTheArgumentsCouldNotBeValid()
    {
        $this->expectException(InvalidHashException::class);
        $serializedArguments = base64_encode('some manipulated arguments string without valid HMAC');
        $referrer = [
            '@controller' => 'Foo',
            '@action' => 'bar',
            'arguments' => $serializedArguments
        ];

        $mockHashService = $this->getMockBuilder(HashService::class)->getMock();
        $mockHashService->expects(self::once())->method('validateAndStripHmac')->with($serializedArguments)->will(self::throwException(new InvalidHashException()));
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
        $mockDispatcher->expects(self::once())->method('dispatch')->with(ActionRequest::class, 'requestDispatched', [$this->actionRequest]);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->will(self::returnValue($mockDispatcher));
        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

        $this->actionRequest->setDispatched(true);
    }

    /**
     * @test
     */
    public function setControllerPackageKeyWithLowercasePackageKeyResolvesCorrectly()
    {
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->expects(self::any())->method('getCaseSensitivePackageKey')->with('acme.testpackage')->will(self::returnValue('Acme.Testpackage'));

        $this->inject($this->actionRequest, 'packageManager', $mockPackageManager);
        $this->actionRequest->setControllerPackageKey('acme.testpackage');

        self::assertEquals('Acme.Testpackage', $this->actionRequest->getControllerPackageKey());
    }

    /**
     * @test
     */
    public function internalArgumentsOfActionRequestOverruleThoseOfTheHttpRequest()
    {
        $this->actionRequest->setArguments(['__internalArgument' => 'action request']);

        $expectedResult = ['__internalArgument' => 'action request'];
        self::assertSame($expectedResult, $this->actionRequest->getInternalArguments());
    }

    /**
     * @test
     */
    public function pluginArgumentsOfActionRequestOverruleThoseOfTheHttpRequest()
    {
        $this->actionRequest->setArguments(['--pluginArgument' => 'action request']);

        $expectedResult = ['pluginArgument' => 'action request'];
        self::assertSame($expectedResult, $this->actionRequest->getPluginArguments());
    }

    /**
     * @test
     */
    public function settingAnArgumentWithIntegerNameWillCastToString()
    {
        $argumentValue = 'amnesia spray';
        $this->actionRequest->setArgument(123, $argumentValue);
        self::assertTrue($this->actionRequest->hasArgument('123'));
        self::assertEquals($argumentValue, $this->actionRequest->getArgument('123'));
    }
}

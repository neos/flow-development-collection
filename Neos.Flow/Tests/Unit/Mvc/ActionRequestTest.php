<?php

declare(strict_types=1);

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
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Testcase for the MVC ActionRequest class
 */
final class ActionRequestTest extends UnitTestCase
{
    /**
     * @var ActionRequest
     */
    protected $actionRequest;

    /**
     * @var ServerRequestInterface
     */
    protected $httpRequest;

    protected function setUp(): void
    {
        $this->httpRequest = $this->createStub(ServerRequestInterface::class);
        $this->actionRequest = ActionRequest::fromHttpRequest($this->httpRequest);
    }

    /**
     * By design, the root request will always be an HTTP request because it is
     * the only of the two types which can be instantiated without having to pass
     * another request as the parent request.
     */
    #[Test]
    public function anActionRequestIsRequiredAsParentRequest()
    {
        self::assertNull($this->actionRequest->getParentRequest());

        $anotherActionRequest = $this->actionRequest->createSubRequest();
        self::assertSame($this->actionRequest, $anotherActionRequest->getParentRequest());
    }

    #[Test]
    public function getHttpRequestReturnsTheHttpRequestWhichIsTheRootOfAllActionRequests()
    {
        $anotherActionRequest = $this->actionRequest->createSubRequest();
        $yetAnotherActionRequest = $anotherActionRequest->createSubRequest();

        self::assertSame($this->httpRequest, $this->actionRequest->getHttpRequest());
        self::assertSame($this->httpRequest, $yetAnotherActionRequest->getHttpRequest());
        self::assertSame($this->httpRequest, $anotherActionRequest->getHttpRequest());
    }

    #[Test]
    public function getMainRequestReturnsTheTopLevelActionRequestWhoseParentIsTheHttpRequest()
    {
        $anotherActionRequest = $this->actionRequest->createSubRequest();
        $yetAnotherActionRequest = $anotherActionRequest->createSubRequest();

        self::assertSame($this->actionRequest, $this->actionRequest->getMainRequest());
        self::assertSame($this->actionRequest, $yetAnotherActionRequest->getMainRequest());
        self::assertSame($this->actionRequest, $anotherActionRequest->getMainRequest());
    }

    #[Test]
    public function isMainRequestChecksIfTheParentRequestIsNotAnHttpRequest()
    {
        $anotherActionRequest = $this->actionRequest->createSubRequest();
        $yetAnotherActionRequest = $anotherActionRequest->createSubRequest();

        self::assertTrue($this->actionRequest->isMainRequest());
        self::assertFalse($anotherActionRequest->isMainRequest());
        self::assertFalse($yetAnotherActionRequest->isMainRequest());
    }

    #[Test]
    public function getControllerObjectNameReturnsObjectNameDerivedFromPreviouslySetControllerInformation()
    {
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->method('getCaseSensitivePackageKey')->with('somepackage')->willReturn(('SomePackage'));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('getCaseSensitiveObjectName')->with('SomePackage\Some\Subpackage\Controller\SomeControllerController')
            ->willReturn(('SomePackage\Some\SubPackage\Controller\SomeControllerController'));

        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);
        $this->inject($this->actionRequest, 'packageManager', $mockPackageManager);

        $this->actionRequest->setControllerPackageKey('somepackage');
        $this->actionRequest->setControllerSubPackageKey('Some\Subpackage');
        $this->actionRequest->setControllerName('SomeController');

        self::assertEquals('SomePackage\Some\SubPackage\Controller\SomeControllerController', $this->actionRequest->getControllerObjectName());
    }

    #[Test]
    public function getControllerObjectNameReturnsAnEmptyStringIfTheResolvedControllerDoesNotExist()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('getCaseSensitiveObjectName')->with('SomePackage\Some\Subpackage\Controller\SomeControllerController')
            ->willReturn((null));

        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->method('getCaseSensitivePackageKey')->with('somepackage')->willReturn(('SomePackage'));

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
    public static function caseSensitiveObjectNames(): \Iterator
    {
        yield [
            'Neos\Foo\Controller\BarController',
            [
                'controllerPackageKey' => 'Neos.Foo',
                'controllerSubpackageKey' => '',
                'controllerName' => 'Bar',
            ]
        ];
        yield [
            'Neos\Foo\Bar\Controller\BazController',
            [
                'controllerPackageKey' => 'Neos.Foo',
                'controllerSubpackageKey' => 'Bar',
                'controllerName' => 'Baz',
            ]
        ];
        yield [
            'Neos\Foo\Bar\Bla\Controller\Baz\QuuxController',
            [
                'controllerPackageKey' => 'Neos.Foo',
                'controllerSubpackageKey' => 'Bar\Bla',
                'controllerName' => 'Baz\Quux',
            ]
        ];
        yield [
            'Neos\Foo\Controller\Bar\BazController',
            [
                'controllerPackageKey' => 'Neos.Foo',
                'controllerSubpackageKey' => '',
                'controllerName' => 'Bar\Baz',
            ]
        ];
        yield [
            'Neos\Foo\Controller\Bar\Baz\QuuxController',
            [
                'controllerPackageKey' => 'Neos.Foo',
                'controllerSubpackageKey' => '',
                'controllerName' => 'Bar\Baz\Quux',
            ]
        ];
    }

    /**
     * @param string $objectName
     * @param array $parts
     */
    #[DataProvider('caseSensitiveObjectNames')]
    #[Test]
    public function setControllerObjectNameSplitsTheGivenObjectNameIntoItsParts($objectName, array $parts)
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('getCaseSensitiveObjectName')->with($objectName)->willReturn(($objectName));
        $mockObjectManager->method('getPackageKeyByObjectName')->with($objectName)->willReturn(($parts['controllerPackageKey']));

        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

        $this->actionRequest->setControllerObjectName($objectName);
        self::assertSame($parts['controllerPackageKey'], $this->actionRequest->getControllerPackageKey());
        self::assertSame($parts['controllerSubpackageKey'], $this->actionRequest->getControllerSubpackageKey());
        self::assertSame($parts['controllerName'], $this->actionRequest->getControllerName());
    }

    #[Test]
    public function setControllerObjectNameThrowsExceptionOnUnknownObjectName()
    {
        $this->expectException(UnknownObjectException::class);
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('getCaseSensitiveObjectName')->willReturn((null));

        $this->inject($this->actionRequest, 'objectManager', $mockObjectManager);

        $this->actionRequest->setControllerObjectName('SomeUnknownControllerObjectName');
    }

    #[Test]
    public function getControllerNameExtractsTheControllerNameFromTheControllerObjectNameToAssureTheCorrectCase()
    {
        /** @var ActionRequest|MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->onlyMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects($this->once())->method('getControllerObjectName')->willReturn(('Neos\MyPackage\Controller\Foo\BarController'));

        $actionRequest->setControllerName('foo\bar');
        self::assertEquals('Foo\Bar', $actionRequest->getControllerName());
    }

    #[Test]
    public function getControllerNameReturnsTheUnknownCasesControllerNameIfNoControllerObjectNameCouldBeDetermined()
    {
        /** @var ActionRequest|MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->onlyMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects($this->once())->method('getControllerObjectName')->willReturn((''));

        $actionRequest->setControllerName('foo\bar');
        self::assertEquals('foo\bar', $actionRequest->getControllerName());
    }

    #[Test]
    public function getControllerSubpackageKeyExtractsTheSubpackageKeyFromTheControllerObjectNameToAssureTheCorrectCase()
    {
        /** @var ActionRequest|MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->onlyMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects($this->once())->method('getControllerObjectName')->willReturn(('Neos\MyPackage\Some\SubPackage\Controller\Foo\BarController'));

        /** @var PackageManager|MockObject $mockPackageManager */
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->method('getCaseSensitivePackageKey')->with('neos.mypackage')->willReturn(('Neos.MyPackage'));
        $this->inject($actionRequest, 'packageManager', $mockPackageManager);

        $actionRequest->setControllerPackageKey('neos.mypackage');
        $actionRequest->setControllerSubpackageKey('some\subpackage');
        self::assertEquals('Some\SubPackage', $actionRequest->getControllerSubpackageKey());
    }

    #[Test]
    public function getControllerSubpackageKeyReturnsNullIfNoSubpackageKeyIsSet()
    {
        /** @var ActionRequest|MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->onlyMethods(['getControllerObjectName'])->getMock();
        $actionRequest->method('getControllerObjectName')->willReturn(('Neos\MyPackage\Controller\Foo\BarController'));

        /** @var PackageManager|MockObject $mockPackageManager */
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->method('getCaseSensitivePackageKey')->with('neos.mypackage')->willReturn(('Neos.MyPackage'));
        $this->inject($actionRequest, 'packageManager', $mockPackageManager);

        $actionRequest->setControllerPackageKey('neos.mypackage');
        self::assertNull($actionRequest->getControllerSubpackageKey());
    }

    #[Test]
    public function getControllerSubpackageKeyReturnsTheUnknownCasesPackageKeyIfNoControllerObjectNameCouldBeDetermined()
    {
        /** @var ActionRequest|MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->onlyMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects($this->once())->method('getControllerObjectName')->willReturn((''));

        /** @var PackageManager|MockObject $mockPackageManager */
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->method('getCaseSensitivePackageKey')->with('neos.mypackage')->willReturn((false));
        $this->inject($actionRequest, 'packageManager', $mockPackageManager);

        $actionRequest->setControllerPackageKey('neos.mypackage');
        $actionRequest->setControllerSubpackageKey('some\subpackage');
        self::assertEquals('some\subpackage', $actionRequest->getControllerSubpackageKey());
    }

    /**
     * Data Provider
     */
    public static function invalidControllerNames(): \Iterator
    {
        //[42],
        //[false],
        yield ['foo_bar_baz'];
    }

    /**
     * @param mixed $invalidControllerName
     */
    #[DataProvider('invalidControllerNames')]
    #[Test]
    public function setControllerNameThrowsExceptionOnInvalidControllerNames($invalidControllerName)
    {
        $this->expectException(InvalidControllerNameException::class);
        $this->actionRequest->setControllerName($invalidControllerName);
    }

    #[Test]
    public function theActionNameCanBeSetAndRetrieved()
    {
        /** @var ActionRequest|MockObject $actionRequest */
        $actionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->onlyMethods(['getControllerObjectName'])->getMock();
        $actionRequest->expects($this->once())->method('getControllerObjectName')->willReturn((''));

        $actionRequest->setControllerActionName('theAction');
        self::assertEquals('theAction', $actionRequest->getControllerActionName());
    }

    /**
     * Data Provider
     */
    public static function invalidActionNames(): \Iterator
    {
        //[42],
        yield [''];
        yield ['FooBar'];
    }

    /**
     * @param mixed $invalidActionName
     */
    #[DataProvider('invalidActionNames')]
    #[Test]
    public function setControllerActionNameThrowsExceptionOnInvalidActionNames($invalidActionName)
    {
        $this->expectException(InvalidActionNameException::class);
        $this->actionRequest->setControllerActionName($invalidActionName);
    }

    #[Test]
    public function theActionNamesCaseIsFixedIfItIsAllLowerCaseAndTheControllerObjectNameIsKnown()
    {
        $mockControllerClassName = 'Mock' . md5(uniqid((string)mt_rand(), true));
        eval('
			class ' . $mockControllerClassName . ' extends \Neos\Flow\Mvc\Controller\ActionController {
				public function someGreatAction() {}
			}
		');

        $mockController = $this->createStub($mockControllerClassName, ['someGreatAction'], [], '', false);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')
            ->with('Neos\Flow\MyControllerObjectName')
            ->willReturn((get_class($mockController)));

        /** @var ActionRequest|MockObject $actionRequest */
        $actionRequest = $this->getAccessibleMock(ActionRequest::class, ['getControllerObjectName'], [], '', false);
        $actionRequest->expects($this->once())->method('getControllerObjectName')->willReturn(('Neos\Flow\MyControllerObjectName'));
        $actionRequest->_set('objectManager', $mockObjectManager);

        $actionRequest->setControllerActionName('somegreat');
        self::assertEquals('someGreat', $actionRequest->getControllerActionName());
    }

    #[Test]
    public function aSingleArgumentCanBeSetWithSetArgumentAndRetrievedWithGetArgument()
    {
        $this->actionRequest->setArgument('someArgumentName', 'theValue');
        self::assertEquals('theValue', $this->actionRequest->getArgument('someArgumentName'));
    }

    #[Test]
    public function setArgumentThrowsAnExceptionOnInvalidArgumentNames()
    {
        $this->expectException(InvalidArgumentNameException::class);
        $this->actionRequest->setArgument('', 'theValue');
    }

    #[Test]
    public function setArgumentDoesNotAllowObjectValuesForRegularArguments()
    {
        $this->expectException(InvalidArgumentTypeException::class);
        $this->actionRequest->setArgument('foo', new \stdClass());
    }

    #[Test]
    public function allArgumentsCanBeSetOrRetrievedAtOnce()
    {
        $arguments = [
            'foo' => 'fooValue',
            'bar' => 'barValue'
        ];

        $this->actionRequest->setArguments($arguments);
        self::assertEquals($arguments, $this->actionRequest->getArguments());
    }

    #[Test]
    public function internalArgumentsAreHandledSeparately()
    {
        $this->actionRequest->setArgument('__someInternalArgument', 'theValue');

        self::assertFalse($this->actionRequest->hasArgument('__someInternalArgument'));
        self::assertEquals('theValue', $this->actionRequest->getInternalArgument('__someInternalArgument'));
        self::assertEquals(['__someInternalArgument' => 'theValue'], $this->actionRequest->getInternalArguments());
    }

    #[Test]
    public function internalArgumentsMayHaveObjectValues()
    {
        $someObject = new \stdClass();

        $this->actionRequest->setArgument('__someInternalArgument', $someObject);

        self::assertSame($someObject, $this->actionRequest->getInternalArgument('__someInternalArgument'));
    }

    #[Test]
    public function pluginArgumentsAreHandledSeparately()
    {
        $this->actionRequest->setArgument('--typo3-flow-foo-viewhelper-paginate', ['@controller' => 'Foo', 'page' => 5]);

        self::assertFalse($this->actionRequest->hasArgument('--typo3-flow-foo-viewhelper-paginate'));
        self::assertEquals(['typo3-flow-foo-viewhelper-paginate' => ['@controller' => 'Foo', 'page' => 5]], $this->actionRequest->getPluginArguments());
    }

    #[Test]
    public function argumentNamespaceCanBeSpecified()
    {
        self::assertSame('', $this->actionRequest->getArgumentNamespace());
        $this->actionRequest->setArgumentNamespace('someArgumentNamespace');
        self::assertSame('someArgumentNamespace', $this->actionRequest->getArgumentNamespace());
    }

    #[Test]
    public function theRepresentationFormatCanBeSetAndRetrieved()
    {
        $this->actionRequest->setFormat('html');
        self::assertEquals('html', $this->actionRequest->getFormat());

        $this->actionRequest->setFormat('doc');
        self::assertEquals('doc', $this->actionRequest->getFormat());

        $this->actionRequest->setFormat('hTmL');
        self::assertEquals('html', $this->actionRequest->getFormat());
    }

    #[Test]
    public function getReferringRequestThrowsAnExceptionIfTheHmacOfTheArgumentsCouldNotBeValid()
    {
        $this->expectException(InvalidHashException::class);
        $serializedArguments = base64_encode('some manipulated arguments string without valid HMAC');
        $referrer = [
            '@controller' => 'Foo',
            '@action' => 'bar',
            'arguments' => $serializedArguments
        ];

        $mockHashService = $this->createMock(HashService::class);
        $mockHashService->expects($this->once())->method('validateAndStripHmac')->with($serializedArguments)->willThrowException(new InvalidHashException());
        $this->inject($this->actionRequest, 'hashService', $mockHashService);

        $this->actionRequest->setArgument('__referrer', $referrer);

        $this->actionRequest->getReferringRequest();
    }

    #[Test]
    public function setControllerPackageKeyWithLowercasePackageKeyResolvesCorrectly()
    {
        $mockPackageManager = $this->createMock(PackageManager::class);
        $mockPackageManager->method('getCaseSensitivePackageKey')->with('acme.testpackage')->willReturn(('Acme.Testpackage'));

        $this->inject($this->actionRequest, 'packageManager', $mockPackageManager);
        $this->actionRequest->setControllerPackageKey('acme.testpackage');

        self::assertEquals('Acme.Testpackage', $this->actionRequest->getControllerPackageKey());
    }

    #[Test]
    public function internalArgumentsOfActionRequestOverruleThoseOfTheHttpRequest()
    {
        $this->actionRequest->setArguments(['__internalArgument' => 'action request']);

        $expectedResult = ['__internalArgument' => 'action request'];
        self::assertSame($expectedResult, $this->actionRequest->getInternalArguments());
    }

    #[Test]
    public function pluginArgumentsOfActionRequestOverruleThoseOfTheHttpRequest()
    {
        $this->actionRequest->setArguments(['--pluginArgument' => 'action request']);

        $expectedResult = ['pluginArgument' => 'action request'];
        self::assertSame($expectedResult, $this->actionRequest->getPluginArguments());
    }

    #[Test]
    public function settingAnArgumentWithIntegerNameWillCastToString()
    {
        $argumentValue = 'amnesia spray';
        $this->actionRequest->setArgument('123', $argumentValue);
        self::assertTrue($this->actionRequest->hasArgument('123'));
        self::assertEquals($argumentValue, $this->actionRequest->getArgument('123'));
    }
}

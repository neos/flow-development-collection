<?php
namespace Neos\Flow\Tests\Unit\Core;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Exception;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the ApplicationContext class
 */
class ApplicationContextTest extends UnitTestCase
{
    /**
     * Data provider with allowed contexts.
     *
     * @return array
     */
    public function allowedContexts()
    {
        return [
            ['Production'],
            ['Testing'],
            ['Development'],

            ['Development/MyLocalComputer'],
            ['Development/MyLocalComputer/Foo'],
            ['Production/SpecialDeployment/LiveSystem'],
        ];
    }

    /**
     * @test
     * @dataProvider allowedContexts
     */
    public function contextStringCanBeSetInConstructorAndReadByCallingToString($allowedContext)
    {
        $context = new ApplicationContext($allowedContext);
        self::assertSame($allowedContext, (string)$context);
    }

    /**
     * Data provider with forbidden contexts.
     *
     * @return array
     */
    public function forbiddenContexts()
    {
        return [
            ['MySpecialContexz'],
            ['Testing123'],
            ['DevelopmentStuff'],
            ['DevelopmentStuff/FooBar'],
        ];
    }

    /**
     * @test
     * @dataProvider forbiddenContexts
     */
    public function constructorThrowsExceptionIfMainContextIsForbidden($forbiddenContext)
    {
        $this->expectException(Exception::class);
        new ApplicationContext($forbiddenContext);
    }

    /**
     * Data provider with expected is*() values for various contexts.
     *
     * @return array
     */
    public function isMethods()
    {
        return [
            'Development' => [
                'contextName' => 'Development',
                'isDevelopment' => true,
                'isProduction' => false,
                'isTesting' => false,
                'parentContext' => null
            ],
            'Development/YourSpecialContext' => [
                'contextName' => 'Development/YourSpecialContext',
                'isDevelopment' => true,
                'isProduction' => false,
                'isTesting' => false,
                'parentContext' => 'Development'
            ],

            'Production' => [
                'contextName' => 'Production',
                'isDevelopment' => false,
                'isProduction' => true,
                'isTesting' => false,
                'parentContext' => null
            ],
            'Production/MySpecialContext' => [
                'contextName' => 'Production/MySpecialContext',
                'isDevelopment' => false,
                'isProduction' => true,
                'isTesting' => false,
                'parentContext' => 'Production'
            ],

            'Testing' => [
                'contextName' => 'Testing',
                'isDevelopment' => false,
                'isProduction' => false,
                'isTesting' => true,
                'parentContext' => null
            ],
            'Testing/MySpecialContext' => [
                'contextName' => 'Testing/MySpecialContext',
                'isDevelopment' => false,
                'isProduction' => false,
                'isTesting' => true,
                'parentContext' => 'Testing'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider isMethods
     */
    public function contextMethodsReturnTheCorrectValues($contextName, $isDevelopment, $isProduction, $isTesting, $parentContext)
    {
        $context = new ApplicationContext($contextName);
        self::assertSame($isDevelopment, $context->isDevelopment());
        self::assertSame($isProduction, $context->isProduction());
        self::assertSame($isTesting, $context->isTesting());
        self::assertSame((string)$parentContext, (string)$context->getParent());
    }

    /**
     * @test
     */
    public function parentContextIsConnectedRecursively()
    {
        $context = new ApplicationContext('Production/Foo/Bar');
        $parentContext = $context->getParent();
        self::assertSame('Production/Foo', (string) $parentContext);

        $rootContext = $parentContext->getParent();
        self::assertSame('Production', (string) $rootContext);
    }

    public function getHierarchyDataProvider(): array
    {
        return [
            ['contextString' => 'Development', 'expectedResult' => ['Development']],
            ['contextString' => 'Testing/Staging', 'expectedResult' => ['Testing', 'Testing/Staging']],
            ['contextString' => 'Production/Staging/Stage1', 'expectedResult' => ['Production', 'Production/Staging', 'Production/Staging/Stage1']],
        ];
    }

    /**
     * @dataProvider getHierarchyDataProvider
     * @test
     * @param string $contextString
     * @param array $expectedResult
     */
    public function getHierarchyTest(string $contextString, array $expectedResult): void
    {
        $context = new ApplicationContext($contextString);
        self::assertSame($expectedResult, $context->getHierarchy());
    }
}

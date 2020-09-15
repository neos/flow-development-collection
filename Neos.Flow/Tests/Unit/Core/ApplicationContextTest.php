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
        $this->assertSame($allowedContext, (string)$context);
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
     * @expectedException \Neos\Flow\Exception
     */
    public function constructorThrowsExceptionIfMainContextIsForbidden($forbiddenContext)
    {
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
        $this->assertSame($isDevelopment, $context->isDevelopment());
        $this->assertSame($isProduction, $context->isProduction());
        $this->assertSame($isTesting, $context->isTesting());
        $this->assertSame((string)$parentContext, (string)$context->getParent());
    }

    /**
     * @test
     */
    public function parentContextIsConnectedRecursively()
    {
        $context = new ApplicationContext('Production/Foo/Bar');
        $parentContext = $context->getParent();
        $this->assertSame('Production/Foo', (string) $parentContext);

        $rootContext = $parentContext->getParent();
        $this->assertSame('Production', (string) $rootContext);
    }
}

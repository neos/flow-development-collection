<?php
namespace Neos\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Controller\MvcPropertyMappingConfiguration;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC Controller Argument
 */
class MvcPropertyMappingConfigurationTest extends UnitTestCase
{
    /**
     * @var MvcPropertyMappingConfiguration
     */
    protected $mvcPropertyMappingConfiguration;

    /**
     *
     */
    public function setUp()
    {
        $this->mvcPropertyMappingConfiguration = new MvcPropertyMappingConfiguration();
    }

    /**
     * @return array Signature: $methodToTestForFluentInterface [, $argumentsForMethod = array() ]
     */
    public function fluentInterfaceMethodsDataProvider()
    {
        return [
            ['allowCreationForSubProperty', ['some.property.path']],
            ['allowModificationForSubProperty', ['some.property.path']],
            ['setTargetTypeForSubProperty', ['some.property.path', 'dummy\Target\Type']],
            ['allowOverrideTargetType'],
        ];
    }

    /**
     * @test
     * @dataProvider fluentInterfaceMethodsDataProvider
     */
    public function respectiveMethodsProvideFluentInterface($methodToTestForFluentInterface, array $argumentsForMethod = [])
    {
        $actualResult = call_user_func_array([$this->mvcPropertyMappingConfiguration, $methodToTestForFluentInterface], $argumentsForMethod);
        $this->assertSame($this->mvcPropertyMappingConfiguration, $actualResult);
    }
}

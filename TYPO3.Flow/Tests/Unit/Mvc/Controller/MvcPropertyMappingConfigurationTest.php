<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the MVC Controller Argument
 *
 * @covers \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfiguration
 */
class MvcPropertyMappingConfigurationTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfiguration
     */
    protected $mvcPropertyMappingConfiguration;

    /**
     *
     */
    public function setUp()
    {
        $this->mvcPropertyMappingConfiguration = new \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfiguration();
    }

    /**
     * @return array Signature: $methodToTestForFluentInterface [, $argumentsForMethod = array() ]
     */
    public function fluentInterfaceMethodsDataProvider()
    {
        return array(
            array('allowCreationForSubProperty', array('some.property.path')),
            array('allowModificationForSubProperty', array('some.property.path')),
            array('setTargetTypeForSubProperty', array('some.property.path', 'dummy\Target\Type')),
            array('allowOverrideTargetType'),
        );
    }

    /**
     * @test
     * @dataProvider fluentInterfaceMethodsDataProvider
     */
    public function respectiveMethodsProvideFluentInterface($methodToTestForFluentInterface, array $argumentsForMethod = array())
    {
        $actualResult = call_user_func_array(array($this->mvcPropertyMappingConfiguration, $methodToTestForFluentInterface), $argumentsForMethod);
        $this->assertSame($this->mvcPropertyMappingConfiguration, $actualResult);
    }
}

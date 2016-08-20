<?php
namespace TYPO3\Flow\Tests\Unit\Object\Configuration;

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
 * Testcase for the object configuration class
 *
 */
class ConfigurationTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Object\Configuration\Configuration
     */
    protected $objectConfiguration;

    /**
     * Prepares everything for a test
     *
     */
    public function setUp()
    {
        $this->objectConfiguration = new \TYPO3\Flow\Object\Configuration\Configuration('TYPO3\Foo\Bar');
    }

    /**
     * Checks if setProperties accepts only valid values
     *
     * @test
     * @expectedException \TYPO3\Flow\Configuration\Exception\InvalidConfigurationException
     */
    public function setPropertiesOnlyAcceptsValidValues()
    {
        $invalidProperties = array(
            'validProperty' => new \TYPO3\Flow\Object\Configuration\ConfigurationProperty('validProperty', 'simple string'),
            'invalidProperty' => 'foo'
        );

        $this->objectConfiguration->setProperties($invalidProperties);
    }

    /**
     * @test
     */
    public function passingAnEmptyArrayToSetPropertiesRemovesAllExistingproperties()
    {
        $someProperties = array(
            'prop1' => new \TYPO3\Flow\Object\Configuration\ConfigurationProperty('prop1', 'simple string'),
            'prop2' => new \TYPO3\Flow\Object\Configuration\ConfigurationProperty('prop2', 'another string')
        );
        $this->objectConfiguration->setProperties($someProperties);
        $this->assertEquals($someProperties, $this->objectConfiguration->getProperties(), 'The set properties could not be retrieved again.');

        $this->objectConfiguration->setProperties(array());
        $this->assertEquals(array(), $this->objectConfiguration->getProperties(), 'The properties have not been cleared.');
    }

    /**
     * Checks if setArguments accepts only valid values
     *
     * @test
     * @expectedException \TYPO3\Flow\Configuration\Exception\InvalidConfigurationException
     */
    public function setArgumentsOnlyAcceptsValidValues()
    {
        $invalidArguments = array(
            1 => new \TYPO3\Flow\Object\Configuration\ConfigurationArgument(1, 'simple string'),
            2 => 'foo'
        );

        $this->objectConfiguration->setArguments($invalidArguments);
    }

    /**
     * @test
     */
    public function passingAnEmptyArrayToSetArgumentsRemovesAllExistingArguments()
    {
        $someArguments = array(
            1 => new \TYPO3\Flow\Object\Configuration\ConfigurationArgument(1, 'simple string'),
            2 => new \TYPO3\Flow\Object\Configuration\ConfigurationArgument(2, 'another string')
        );
        $this->objectConfiguration->setArguments($someArguments);
        $this->assertEquals($someArguments, $this->objectConfiguration->getArguments(), 'The set arguments could not be retrieved again.');

        $this->objectConfiguration->setArguments(array());
        $this->assertEquals(array(), $this->objectConfiguration->getArguments(), 'The constructor arguments have not been cleared.');
    }

    /**
     * @test
     */
    public function setFactoryObjectNameAcceptsValidClassNames()
    {
        $this->objectConfiguration->setFactoryObjectName(__CLASS__);
        $this->assertSame(__CLASS__, $this->objectConfiguration->getFactoryObjectName());
    }

    /**
     * @test
     */
    public function setFactoryMethodNameAcceptsValidStrings()
    {
        $this->objectConfiguration->setFactoryMethodName('someMethodName');
        $this->assertSame('someMethodName', $this->objectConfiguration->getFactoryMethodName());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setFactoryMethodNameRejectsAnythingElseThanAString()
    {
        $this->objectConfiguration->setFactoryMethodName(array());
    }

    /**
     * @test
     */
    public function theDefaultFactoryMethodNameIsCreate()
    {
        $this->assertSame('create', $this->objectConfiguration->getFactoryMethodName());
    }
}

<?php
namespace Neos\Flow\Tests\Unit\ObjectManagement\Configuration;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\ObjectManagement\Configuration;

/**
 * Testcase for the object configuration class
 */
class ConfigurationTest extends UnitTestCase
{
    /**
     * @var Configuration\Configuration
     */
    protected $objectConfiguration;

    /**
     * Prepares everything for a test
     *
     */
    public function setUp()
    {
        $this->objectConfiguration = new Configuration\Configuration('Neos\Foo\Bar');
    }

    /**
     * Checks if setProperties accepts only valid values
     *
     * @test
     * @expectedException \Neos\Flow\Configuration\Exception\InvalidConfigurationException
     */
    public function setPropertiesOnlyAcceptsValidValues()
    {
        $invalidProperties = [
            'validProperty' => new Configuration\ConfigurationProperty('validProperty', 'simple string'),
            'invalidProperty' => 'foo'
        ];

        $this->objectConfiguration->setProperties($invalidProperties);
    }

    /**
     * @test
     */
    public function passingAnEmptyArrayToSetPropertiesRemovesAllExistingproperties()
    {
        $someProperties = [
            'prop1' => new Configuration\ConfigurationProperty('prop1', 'simple string'),
            'prop2' => new Configuration\ConfigurationProperty('prop2', 'another string')
        ];
        $this->objectConfiguration->setProperties($someProperties);
        $this->assertEquals($someProperties, $this->objectConfiguration->getProperties(), 'The set properties could not be retrieved again.');

        $this->objectConfiguration->setProperties([]);
        $this->assertEquals([], $this->objectConfiguration->getProperties(), 'The properties have not been cleared.');
    }

    /**
     * Checks if setArguments accepts only valid values
     *
     * @test
     * @expectedException \Neos\Flow\Configuration\Exception\InvalidConfigurationException
     */
    public function setArgumentsOnlyAcceptsValidValues()
    {
        $invalidArguments = [
            1 => new Configuration\ConfigurationArgument(1, 'simple string'),
            2 => 'foo'
        ];

        $this->objectConfiguration->setArguments($invalidArguments);
    }

    /**
     * @test
     */
    public function passingAnEmptyArrayToSetArgumentsRemovesAllExistingArguments()
    {
        $someArguments = [
            1 => new Configuration\ConfigurationArgument(1, 'simple string'),
            2 => new Configuration\ConfigurationArgument(2, 'another string')
        ];
        $this->objectConfiguration->setArguments($someArguments);
        $this->assertEquals($someArguments, $this->objectConfiguration->getArguments(), 'The set arguments could not be retrieved again.');

        $this->objectConfiguration->setArguments([]);
        $this->assertEquals([], $this->objectConfiguration->getArguments(), 'The constructor arguments have not been cleared.');
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
        $this->objectConfiguration->setFactoryMethodName([]);
    }

    /**
     * @test
     */
    public function theDefaultFactoryMethodNameIsCreate()
    {
        $this->assertSame('create', $this->objectConfiguration->getFactoryMethodName());
    }
}

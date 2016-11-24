<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\ObjectAccess;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the Object configuration via Objects.yaml
 */
class ConfigurationTest extends FunctionalTestCase
{
    /**
     * See the configuration in Testing/Objects.yaml
     * @test
     */
    public function configuredObjectDWillGetAssignedObjectFWithCorrectlyConfiguredConstructorValue()
    {
        $instance = $this->objectManager->get(Fixtures\PrototypeClassD::class);
        /** @var $instanceE Fixtures\PrototypeClassE */
        $instanceE = ObjectAccess::getProperty($instance, 'objectE', true);
        $this->assertEquals('The constructor set value', $instanceE->getNullValue());
    }
}

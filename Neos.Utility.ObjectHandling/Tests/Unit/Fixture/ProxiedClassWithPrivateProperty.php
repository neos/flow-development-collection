<?php
namespace Neos\Utility\ObjectHandling\Tests\Unit\Fixture;

/*
 * This file is part of the Neos.Utility.ObjectHandling package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;

/**
 * A class that is has been transformed into a Flow object management proxy
 */
class ProxiedClassWithPrivateProperty_Original
{
    private $property = 'original';

    public function getProperty(): string
    {
        return $this->property;
    }
}

/**
 * A class that is a Flow object management proxy
 */
class ProxiedClassWithPrivateProperty extends ProxiedClassWithPrivateProperty_Original implements ProxyInterface
{
}

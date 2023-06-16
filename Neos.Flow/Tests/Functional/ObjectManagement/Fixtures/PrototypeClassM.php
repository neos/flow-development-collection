<?php
declare(strict_types=1);
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A class of scope prototype in the style of a read model with an optional straight value
 */
class PrototypeClassM
{
    #[Flow\InjectConfiguration('tests.functional.settingInjection.someSetting')]
    public ValueObjectClassB $configuration;
}

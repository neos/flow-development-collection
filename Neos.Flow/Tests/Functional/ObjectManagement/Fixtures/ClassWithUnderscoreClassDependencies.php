<?php
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
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SubNamespace\SubNamespace_Underscores;

/**
 * A class which has dependencies to classes with underscores in their name
 */
class ClassWithUnderscoreClassDependencies
{
    /**
     * @Flow\Inject(lazy=false)
     * @var SubNamespace_Underscores
     */
    public $classWithUnderscores;
}

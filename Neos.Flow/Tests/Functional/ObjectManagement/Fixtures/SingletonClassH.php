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

/**
 * Object with nullable injected service with default value of null
 */
#[Flow\Scope("singleton")]
class SingletonClassH
{
    /**
     * @deprecated Singletons with a default value of null as constructor are deprecated, but we ensure that Flows object management does not crash.
     * The property declaration is simply redundant as the dependency is always null and never set by the object management - even if there is a union with another type.
     */
    public function __construct(
       public ?InterfaceA $interfaceA = null
    ) {
    }
}

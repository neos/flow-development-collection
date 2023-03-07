<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP8;

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

class ClassWithConstructorInjectedConfiguration
{
    public function __construct(
        #[Flow\InjectConfiguration(path: "tests.functional.settingInjection.someSetting")]
        public ?string $someSetting = null
    )
    {
    }
}

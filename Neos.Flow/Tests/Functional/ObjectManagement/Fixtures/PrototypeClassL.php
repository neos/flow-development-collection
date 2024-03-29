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
 * A readonly class of scope prototype with settings injected in the constructor
 */
readonly class PrototypeClassL
{
    public function __construct(
        #[Flow\InjectConfiguration(path: 'tests.functional.settingInjection.someSetting')]
        public string $value
    ) {
        assert($this->value !== '');
    }
}

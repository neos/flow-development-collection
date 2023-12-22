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

use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Annotations as Flow;

/**
 * A class for testing setting injection
 */
class ClassWithInjectedCache
{
    /**
     * @Flow\InjectCache(identifier="Flow_Monitor")
     * @var StringFrontend
     */
    protected $cacheByAnnotation;

    #[Flow\InjectCache(identifier: 'Flow_Monitor')]
    protected StringFrontend $cacheByAttribute;

    public function getCacheInjectedViaAnnotation()
    {
        return $this->cacheByAnnotation;
    }

    public function getCacheInjectedViaAttribute()
    {
        return $this->cacheByAttribute;
    }
}

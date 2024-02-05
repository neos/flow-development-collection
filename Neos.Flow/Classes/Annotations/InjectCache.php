<?php
namespace Neos\Flow\Annotations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Used to enable property injection for cache frontends.
 *
 * Flow will build Dependency Injection code for the property and try
 * to inject the specified cache.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("PROPERTY")
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class InjectCache
{
    /**
     * Identifier for the Cache that will be injected.
     *
     * Example: Neos_Fusion_Content
     *
     * @var string
     */
    public $identifier;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }
}

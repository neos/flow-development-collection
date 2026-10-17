<?php

namespace Neos\Flow\ResourceManagement\Target;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Http\Message\UriInterface;

/**
 * Allows resource uris to be built with and absolute uri
 *
 * For targets implementing this interface, setAbsoluteBaseUri() must be invoked getting a resource uri
 *
 * - {@see TargetInterface::getPublicStaticResourceUri}
 * - {@see TargetInterface::getPublicPersistentResourceUri}
 */
interface AbsoluteBaseUriAwareTarget
{
    public function setAbsoluteBaseUri(UriInterface $baseUri): void;
}

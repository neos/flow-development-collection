<?php
namespace Neos\Flow\Cache\Backend;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Marker interface to denote backends that use the
 * Flow specific constructor for backends and must
 * be instantiated differently than the new standalone
 * backends.
 * As this interface is just to help transition it is
 * deprecated right away.
 *
 * @deprecated
 */
interface FlowSpecificBackendInterface
{
}

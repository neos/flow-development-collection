<?php
namespace Neos\Flow\Log;

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
 * Marker interface for the system logger.
 * Convenience interface, you should instead use the factory to get the "systemLogger"
 * or simply inject Psr\Log\LoggerInterface
 *
 * @deprecated since Flow 6.0
 */
interface PsrSystemLoggerInterface extends \Psr\Log\LoggerInterface
{
}

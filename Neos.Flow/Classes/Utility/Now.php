<?php
namespace Neos\Flow\Utility;

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
 * A unique DateTime object which serves as a reliable reference for the time "now"
 * for all parts of Flow and its packages. It also improves testability of code
 * relying on a certain time.
 *
 * At any place you'd normally call PHP's time() function or create a DateTime
 * object with the current time, you can instead use this instance.
 *
 * @Flow\Scope("singleton")
 * @api
 * TODO: Change to \DateTimeImmutable for next major version after 3.0
 */
class Now extends \DateTime
{
}

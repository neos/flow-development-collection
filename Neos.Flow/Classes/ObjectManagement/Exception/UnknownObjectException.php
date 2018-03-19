<?php
namespace Neos\Flow\ObjectManagement\Exception;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Container\NotFoundExceptionInterface;

/**
 * "Unknown Object" Exception
 *
 * @api
 */
class UnknownObjectException extends \Neos\Flow\ObjectManagement\Exception implements NotFoundExceptionInterface
{
}

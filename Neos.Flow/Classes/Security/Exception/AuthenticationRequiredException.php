<?php
namespace Neos\Flow\Security\Exception;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;

/**
 * An "AccessDenied" Exception
 *
 * @api
 */
class AuthenticationRequiredException extends \Neos\Flow\Security\Exception
{
    /**
     * @var integer
     */
    protected $statusCode = 401;

    /**
     * @var ActionRequest
     */
    public $interceptedRequest;
}

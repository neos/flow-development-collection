<?php
namespace Neos\Flow\Mvc\Exception;

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
 * A "No Such View" exception
 *
 * @api
 */
class NoSuchViewException extends \Neos\Flow\Mvc\Exception
{
    /**
     * @var integer
     */
    protected $statusCode = 500;
}

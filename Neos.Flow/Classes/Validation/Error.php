<?php
namespace Neos\Flow\Validation;

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
 * This object holds a validation error.
 *
 */
class Error extends \Neos\Error\Messages\Error
{
    /**
     * @var string
     */
    protected $message = 'Unknown validation error';

    /**
     * @var string
     */
    protected $code = 1201447005;
}

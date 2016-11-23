<?php
namespace Neos\Error\Messages;

/*
 * This file is part of the Neos.Error.Messages package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An object representation of a generic warning. Subclass this to create
 * more specific warnings if necessary.
 *
 * @api
 */
class Warning extends Message
{
    /**
     * The severity of this message ('Warning').
     * @var string
     */
    protected $severity = self::SEVERITY_WARNING;
}

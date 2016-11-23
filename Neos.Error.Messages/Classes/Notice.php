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
 * An object representation of a generic notice. Subclass this to create
 * more specific notices if necessary.
 *
 * @api
 */
class Notice extends Message
{
    /**
     * The severity of this message ('Notice').
     * @var string
     */
    protected $severity = self::SEVERITY_NOTICE;
}

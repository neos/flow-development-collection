<?php
namespace TYPO3\Flow\Error;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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

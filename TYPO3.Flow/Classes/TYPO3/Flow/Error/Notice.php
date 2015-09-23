<?php
namespace TYPO3\Flow\Error;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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

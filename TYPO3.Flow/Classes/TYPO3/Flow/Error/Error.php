<?php
namespace TYPO3\Flow\Error;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * An object representation of a generic error. Subclass this to create
 * more specific errors if necessary.
 *
 * @api
 */
class Error extends Message
{
    /**
     * The severity of this message ('Error').
     * @var string
     */
    protected $severity = self::SEVERITY_ERROR;
}

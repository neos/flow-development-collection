<?php
namespace TYPO3\Flow\Validation;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */


/**
 * This object holds a validation error.
 *
 */
class Error extends \TYPO3\Flow\Error\Error
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

<?php
namespace TYPO3\Flow\Property\Exception;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A "TargetNotFound" Exception
 *
 * @api
 */
class TargetNotFoundException extends \TYPO3\Flow\Property\Exception
{
    /**
     * @var integer
     */
    protected $statusCode = 404;
}

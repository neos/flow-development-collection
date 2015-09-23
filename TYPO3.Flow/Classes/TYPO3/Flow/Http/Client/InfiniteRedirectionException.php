<?php
namespace TYPO3\Flow\Http\Client;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * An HTTP exception occuring if an endless Location: redirect is suspect to happen
 *
 * @api
 */
class InfiniteRedirectionException extends \TYPO3\Flow\Exception
{
}

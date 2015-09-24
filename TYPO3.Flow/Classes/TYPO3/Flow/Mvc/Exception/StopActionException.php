<?php
namespace TYPO3\Flow\Mvc\Exception;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * This exception is thrown by a controller to stop the execution of the current
 * action and return the control to the dispatcher. The dispatcher catches this
 * exception and - depending on the "dispatched" status of the request - either
 * continues dispatching the request or returns control to the request handler.
 *
 * See the Action Controller's forward() and redirectToUri() methods for more information.
 *
 * @api
 */
class StopActionException extends \TYPO3\Flow\Mvc\Exception
{
}

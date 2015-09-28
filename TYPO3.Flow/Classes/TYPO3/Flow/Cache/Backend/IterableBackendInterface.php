<?php
namespace TYPO3\Flow\Cache\Backend;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A contract for a cache backend which provides Iterator functionality.
 *
 * @api
 */
interface IterableBackendInterface extends BackendInterface, \Iterator
{
}

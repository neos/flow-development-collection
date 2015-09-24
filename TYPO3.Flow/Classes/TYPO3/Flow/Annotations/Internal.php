<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Used to mark a command as internal - it will not be shown in
 * CLI help output.
 *
 * Usually used for framework purposes only.
 *
 * @Annotation
 * @Target("METHOD")
 */
final class Internal
{
}

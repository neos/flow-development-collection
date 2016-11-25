<?php
namespace Neos\Flow\Annotations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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

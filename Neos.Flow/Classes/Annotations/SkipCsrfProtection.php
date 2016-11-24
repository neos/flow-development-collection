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
 * Action methods marked with this annotation will not be secured
 * against CSRF.
 *
 * Since CSRF is a risk for write operations, this is useful for read-only
 * actions. The overhead for CRSF token generation and validation can be
 * skipped in those cases.
 *
 * @Annotation
 * @Target("METHOD")
 */
final class SkipCsrfProtection
{
}

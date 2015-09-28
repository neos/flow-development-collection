<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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

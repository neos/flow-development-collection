<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
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
final class SkipCsrfProtection {}

?>
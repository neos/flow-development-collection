<?php
namespace TYPO3\Eel;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Package\Package as BasePackage;
use TYPO3\Flow\Annotations as Flow;

/**
 * Package base class of the Eel package.
 *
 * @Flow\Scope("singleton")
 */
class Package extends BasePackage {

	const EelExpressionRecognizer = '/
			^\${(?P<exp>
				(?:
					{ (?P>exp) }			# match object literal expression recursively
					|[^{}"\']+				# simple eel expression without quoted strings
					|"[^"\\\\]*				# double quoted strings with possibly escaped double quotes
						(?:
							\\\\.			# escaped character (quote)
							[^"\\\\]*		# unrolled loop following Jeffrey E.F. Friedl
						)*"
					|\'[^\'\\\\]*			# single quoted strings with possibly escaped single quotes
						(?:
							\\\\.			# escaped character (quote)
							[^\'\\\\]*		# unrolled loop following Jeffrey E.F. Friedl
						)*\'
				)*
            )}
			$/x';
}

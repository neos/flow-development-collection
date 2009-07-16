<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Resource;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A resource processor for making adjustments to resources
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Processor {

	const PREFIX_RELATIVE_LINKS = '/(src="|href="|url\()(?!(\/|http|#))/iUu';

	/**
	 * Prepends the given prefix to relative paths in links, css, ...
	 *
	 * @param string $HTML
	 * @param string $pathPrefix
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	static public function prefixRelativePathsInHTML($HTML, $pathPrefix) {
		return preg_replace(self::PREFIX_RELATIVE_LINKS, '$1' . $pathPrefix, $HTML);
	}
}

?>
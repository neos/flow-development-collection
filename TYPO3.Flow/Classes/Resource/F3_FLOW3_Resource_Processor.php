<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Resource;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Resource
 * @version $Id$
 */

/**
 *
 * @package FLOW3
 * @subpackage Resource
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Processor {

	const PREFIX_RELATIVE_LINKS = '/(src="|href="|url\()(?!(\/|http))/iUu';

	/**
	 * Prepends the given prefix to relative paths in links, css, ...
	 *
	 * @param string $HTML
	 * @param string $pathPrefix
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @todo Make regular expression water-tight
	 */
	static public function adjustRelativePathsInHTML($HTML, $pathPrefix) {
		return preg_replace(self::PREFIX_RELATIVE_LINKS, '$1' . $pathPrefix, $HTML);
	}
}

?>
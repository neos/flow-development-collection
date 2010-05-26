<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Locale;

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
 * The Locale Interface
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface LocaleInterface {

	/**
	 * Returns the language defined in this locale
	 *
	 * @return string The language identifier
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLanguage();

	/**
	 * Returns the script defined in this locale
	 *
	 * @return string The script identifier
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getScript();

	/**
	 * Returns the region defined in this locale
	 *
	 * @return string The region identifier
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRegion();

	/**
 	 * Returns the variant defined in this locale
 	 *
 	 * @return string The variant identifier
 	 * @author Karol Gusak <firstname@lastname.eu>
 	 */
 	public function getVariant();

 	/**
 	 * Returns the string identifier of this locale
 	 *
 	 * @return string The locale identifier (tag)
 	 * @author Karol Gusak <firstname@lastname.eu>
 	 */
 	public function __toString();

}
?>
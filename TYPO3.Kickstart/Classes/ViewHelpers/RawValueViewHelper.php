<?php
declare(ENCODING = 'utf-8');
namespace F3\Kickstart\ViewHelpers;

/*                                                                        *
 * This script belongs to the FLOW3 package "Kickstart".                  *
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
 * Wrapper to pass through values as-is
 *
 * = Examples =
 *
 * <code title="Example">
 * <k:rawValue>{textWith>Add>AndStuff}</k:rawValue>
 * </code>
 *
 * Output:
 * textWith>Add>AndStuff
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class RawValueViewHelper extends \F3\Fluid\Core\ViewHelper\AbstractViewHelper {

	protected $objectAccessorPostProcessorEnabled = FALSE;

	/**
	 * Emit raw value
	 *
	 * @return string The altered string.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function render() {
		return $this->renderChildren();
	}

}
?>
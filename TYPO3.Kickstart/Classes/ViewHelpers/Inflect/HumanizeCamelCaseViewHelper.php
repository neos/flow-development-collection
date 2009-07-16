<?php
declare(ENCODING = 'utf-8');
namespace F3\Kickstart\ViewHelpers\Inflect;

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
 * Humanize a camel cased value
 *
 * = Examples =
 *
 * <code title="Example">
 * <k:inflect.humanizeCamelCase>{CamelCasedModelName}</k:inflect.humanizeCamelCase>
 * </code>
 *
 * Output:
 * Camel cased model name
 *
 * @version $Id: $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class HumanizeCamelCaseViewHelper extends \F3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var F3\Kickstart\Utility\Inflector
	 * @inject
	 */
	protected $inflector;

	/**
	 * Humanize a model name
	 *
	 * @param boolean $lowercase Wether the result should be lowercased 
	 * @return string The humanized string
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function render($lowercase = FALSE) {
		$content = $this->renderChildren();
		return $this->inflector->humanizeCamelCase($content, $lowercase);
	}
}
?>
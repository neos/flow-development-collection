<?php
namespace TYPO3\FLOW3\MVC\CLI;

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
 * Represents a Command
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Command {

	/**
	 * @var string
	 */
	protected $controllerClassName;

	/**
	 * @var string
	 */
	protected $controllerCommandName;

	/**
	 * @var string
	 */
	protected $commandIdentifier;

	/**
	 * Constructor
	 *
	 * @param string $controllerClassName Class name of the controller providing the command
	 * @param string $controllerCommandName Command name, i.e. the method name of the command, without the "Command" suffix
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($controllerClassName, $controllerCommandName) {
		$this->controllerClassName = $controllerClassName;
		$this->controllerCommandName = $controllerCommandName;

		$matchCount = preg_match('/^(?P<PackageNamespace>\w+(?:\\\\\w+)+)\\\\Command\\\\(?P<ControllerName>\w+)CommandController$/', $controllerClassName, $matches);
		if ($matchCount !== 1) {
			throw new \InvalidArgumentException('Invalid controller class name "' . $controllerClassName . '"', 1305100019);
		}

		$this->commandIdentifier = strtolower(str_replace('\\', '.', $matches['PackageNamespace']) . ':' . $matches['ControllerName'] . ':' . $controllerCommandName);
	}

	/**
	 * Returns the command identifier for this command
	 *
	 * @return string The command identifier for this command, following the pattern packagekey:controllername:commandname
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCommandIdentifier() {
		return $this->commandIdentifier;
	}

	/**
	 * Returns a short description of this command
	 *
	 * @return string A short description
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getShortDescription() {
		$class = new \TYPO3\FLOW3\Reflection\MethodReflection($this->controllerClassName, $this->controllerCommandName . 'Command');
		$lines = explode(chr(10), $class->getDescription());
		return (count($lines) > 0) ? $lines[0] : '<no description available>';
	}

	/**
	 * Tells if this command is internal and thus should not be exposed through help texts, user documentation etc.
	 * Internall commands are still accessible through the regular command line interface, but should not be used
	 * by users.
	 *
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isInternal() {
		$shortDescription = $this->getShortDescription();
		return (strtolower(substr($shortDescription, 0, 9)) === 'internal:');
	}
}
?>
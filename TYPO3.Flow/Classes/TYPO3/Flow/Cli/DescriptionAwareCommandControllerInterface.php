<?php
namespace TYPO3\Flow\Cli;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Object\ObjectManagerInterface;

/**
 * An interface which allows a CommandController to tweak command descriptions before they are displayed to the user.
 *
 * @api
 */
interface DescriptionAwareCommandControllerInterface {

	/**
	 * Processes the given short description of the specified command.
	 *
	 * @param string $controllerCommandName Name of the command the description is referring to, for example "flush"
	 * @param string $shortDescription The short command description so far
	 * @param ObjectManagerInterface $objectManager The object manager, can be used to access further information necessary for rendering the description
	 * @return string the possibly modified short command description
	 * @api
	 */
	static public function processShortDescription($controllerCommandName, $shortDescription, ObjectManagerInterface $objectManager);

	/**
	 * Processes the given description of the specified command.
	 *
	 * @param string $controllerCommandName Name of the command the description is referring to, for example "flush"
	 * @param string $description The command description so far
	 * @param ObjectManagerInterface $objectManager The object manager, can be used to access further information necessary for rendering the description
	 * @return string the possibly modified command description
	 * @api
	 */
	static public function processDescription($controllerCommandName, $description, ObjectManagerInterface $objectManager);

}
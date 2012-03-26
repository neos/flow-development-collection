<?php
namespace TYPO3\FLOW3\Mvc\Exception;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An "Ambiguous command identifier" exception
 *
 */
class AmbiguousCommandIdentifierException extends CommandException {

	/**
	 * @var array<\TYPO3\FLOW3\Cli\Command>
	 */
	protected $matchingCommands = array();

	/**
	 * Overwrites parent constructor to be able to inject matching commands.
	 *
	 * @param string $message
	 * @param integer $code
	 * @param \Exception $previousException
	 * @param array<\TYPO3\FLOW3\Cli\Command> $matchingCommands Commands that matched the command identifier
	 * @see \Exception
	 */
	public function __construct($message = '', $code = 0, \Exception $previousException = NULL, array $matchingCommands) {
		$this->matchingCommands = $matchingCommands;
		parent::__construct($message, $code, $previousException);
	}

	/**
	 * @return array<\TYPO3\FLOW3\Cli\Command>
	 */
	public function getMatchingCommands() {
		return $this->matchingCommands;
	}

}
?>
<?php
namespace TYPO3\Flow\Mvc\Exception;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * An "Ambiguous command identifier" exception
 *
 */
class AmbiguousCommandIdentifierException extends CommandException
{
    /**
     * @var array<\TYPO3\Flow\Cli\Command>
     */
    protected $matchingCommands = array();

    /**
     * Overwrites parent constructor to be able to inject matching commands.
     *
     * @param string $message
     * @param integer $code
     * @param \Exception $previousException
     * @param array<\TYPO3\Flow\Cli\Command> $matchingCommands Commands that matched the command identifier
     * @see \Exception
     */
    public function __construct($message = '', $code = 0, \Exception $previousException = null, array $matchingCommands)
    {
        $this->matchingCommands = $matchingCommands;
        parent::__construct($message, $code, $previousException);
    }

    /**
     * @return array<\TYPO3\Flow\Cli\Command>
     */
    public function getMatchingCommands()
    {
        return $this->matchingCommands;
    }
}

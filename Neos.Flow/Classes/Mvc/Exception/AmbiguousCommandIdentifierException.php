<?php
namespace Neos\Flow\Mvc\Exception;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An "Ambiguous command identifier" exception
 *
 */
class AmbiguousCommandIdentifierException extends CommandException
{
    /**
     * @var array<\Neos\Flow\Cli\Command>
     */
    protected $matchingCommands = [];

    /**
     * Overwrites parent constructor to be able to inject matching commands.
     *
     * @param string $message
     * @param integer $code
     * @param \Exception $previousException
     * @param array<\Neos\Flow\Cli\Command> $matchingCommands Commands that matched the command identifier
     * @see \Exception
     */
    public function __construct($message = '', $code = 0, \Exception $previousException = null, array $matchingCommands)
    {
        $this->matchingCommands = $matchingCommands;
        parent::__construct($message, $code, $previousException);
    }

    /**
     * @return array<\Neos\Flow\Cli\Command>
     */
    public function getMatchingCommands()
    {
        return $this->matchingCommands;
    }
}

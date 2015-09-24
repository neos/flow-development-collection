<?php
namespace TYPO3\Flow\Security\Authentication;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;

/**
 * Contract for an authentication entry point
 */
interface EntryPointInterface
{
    /**
     * Sets the options array
     *
     * @param array $options An array of configuration options
     * @return void
     */
    public function setOptions(array $options);

    /**
     * Returns the options array
     *
     * @return array An array of configuration options
     */
    public function getOptions();

    /**
     * Starts the authentication. (e.g. redirect to login page or send 401 HTTP header)
     *
     * @param \TYPO3\Flow\Http\Request $request The current request
     * @param \TYPO3\Flow\Http\Response $response The current response
     * @return void
     */
    public function startAuthentication(Request $request, Response $response);
}

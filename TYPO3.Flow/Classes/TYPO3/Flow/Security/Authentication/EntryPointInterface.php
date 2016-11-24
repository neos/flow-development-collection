<?php
namespace TYPO3\Flow\Security\Authentication;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
     * @param Request $request The current request
     * @param Response $response The current response
     * @return void
     */
    public function startAuthentication(Request $request, Response $response);
}

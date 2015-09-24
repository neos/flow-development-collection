<?php
namespace TYPO3\Flow\Http\Client;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;

/**
 * Interface for a Request Engine which can be used by a HTTP Client implementation
 * for sending requests and returning responses.
 */
interface RequestEngineInterface
{
    /**
     * Sends the given HTTP request
     *
     * @param \TYPO3\Flow\Http\Request $request
     * @return \TYPO3\Flow\Http\Response
     * @throws \TYPO3\Flow\Http\Exception
     */
    public function sendRequest(Request $request);
}

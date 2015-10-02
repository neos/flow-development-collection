<?php
namespace TYPO3\Flow\Http\Client;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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

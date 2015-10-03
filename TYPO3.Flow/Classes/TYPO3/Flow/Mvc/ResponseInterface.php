<?php
namespace TYPO3\Flow\Mvc;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A generic and very basic response implementation
 *
 * @api
 */
interface ResponseInterface
{
    /**
     * Overrides and sets the content of the response
     *
     * @param string $content The response content
     * @return void
     * @api
     */
    public function setContent($content);

    /**
     * Appends content to the already existing content.
     *
     * @param string $content More response content
     * @return void
     * @api
     */
    public function appendContent($content);

    /**
     * Returns the response content without sending it.
     *
     * @return string The response content
     * @api
     */
    public function getContent();

    /**
     * Sends the response
     *
     * @return void
     * @api
     */
    public function send();
}

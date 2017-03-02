<?php
namespace Neos\Flow\Mvc;

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
 * A generic and very basic response implementation
 *
 * @api
 */
class Response implements ResponseInterface
{
    /**
     * @var string
     */
    protected $content = null;

    /**
     * Overrides and sets the content of the response
     *
     * @param string $content The response content
     * @return void
     * @api
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Appends content to the already existing content.
     *
     * @param string $content More response content
     * @return void
     * @api
     */
    public function appendContent($content)
    {
        $this->content .= $content;
    }

    /**
     * Returns the response content without sending it.
     *
     * @return string The response content
     * @api
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sends the response
     *
     * @return void
     * @api
     */
    public function send()
    {
        if ($this->content !== null) {
            echo $this->getContent();
        }
    }

    /**
     * Returns the content of the response.
     *
     * @return string
     * @api
     */
    public function __toString()
    {
        return $this->getContent();
    }
}

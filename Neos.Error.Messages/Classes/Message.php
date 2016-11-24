<?php
namespace Neos\Error\Messages;

/*
 * This file is part of the Neos.Error.Messages package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * An object representation of a generic message. Usually, you will use Error, Warning or Notice instead of this one.
 *
 * @api
 */
class Message
{
    const SEVERITY_NOTICE = 'Notice';
    const SEVERITY_WARNING = 'Warning';
    const SEVERITY_ERROR = 'Error';
    const SEVERITY_OK = 'OK';

    /**
     * The error message, could also be a key for translation.
     * @var string
     */
    protected $message = '';

    /**
     * An optional title for the message (used eg. in flashMessages).
     * @var string
     */
    protected $title = '';

    /**
     * The error code.
     * @var integer
     */
    protected $code = null;

    /**
     * The message arguments. Will be replaced in the message body.
     * @var array
     */
    protected $arguments = [];

    /**
     * The severity of this message ('OK'), overwrite in your own implementation.
     * @var string
     */
    protected $severity = self::SEVERITY_OK;

    /**
     * Constructs this error
     *
     * @param string $message An english error message which is used if no other error message can be resolved
     * @param integer $code A unique error code
     * @param array $arguments Array of arguments to be replaced in message
     * @param string $title optional title for the message
     * @api
     */
    public function __construct($message, $code = null, array $arguments = [], $title = '')
    {
        $this->message = $message;
        $this->code = $code;
        $this->arguments = $arguments;
        $this->title = $title;
    }

    /**
     * Returns the error message
     *
     * @return string The error message
     * @api
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns the error code
     *
     * @return integer The error code
     * @api
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return array
     * @api
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @return string
     * @api
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     * @api
     */
    public function getSeverity()
    {
        return $this->severity;
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->arguments !== []) {
            return vsprintf($this->message, $this->arguments);
        } else {
            return $this->message;
        }
    }

    /**
     * Converts this error into a string
     *
     * @return string
     * @api
     */
    public function __toString()
    {
        return $this->render();
    }
}

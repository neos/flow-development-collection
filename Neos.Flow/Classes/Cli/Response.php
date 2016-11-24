<?php
namespace Neos\Flow\Cli;

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
 * A CLI specific response implementation
 *
 */
class Response extends \Neos\Flow\Mvc\Response
{
    /**
     * Constants for output styles
     */
    const STYLE_BRIGHT = 1;
    const STYLE_FAINT = 2;
    const STYLE_ITALIC = 3;
    const STYLE_UNDERLINED = 4;
    const STYLE_INVERSE = 7;
    const STYLE_STRIKETHROUGH = 9;
    const STYLE_ERROR = 31;
    const STYLE_SUCCESS = 32;

    /**
     * Constants for output formats
     */
    const OUTPUTFORMAT_RAW = 1;
    const OUTPUTFORMAT_PLAIN = 2;
    const OUTPUTFORMAT_STYLED = 3;

    /**
     * @var integer
     */
    private $exitCode = 0;

    /**
     * @var
     */
    private $colorSupport;

    /**
     * @var
     */
    private $outputFormat = self::OUTPUTFORMAT_STYLED;

    /**
     * Sets the numerical exit code which should be returned when exiting this application.
     *
     * @param integer $exitCode
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function setExitCode($exitCode)
    {
        if (!is_integer($exitCode)) {
            throw new \InvalidArgumentException(sprintf('Tried to set invalid exit code. The value must be integer, %s given.', gettype($exitCode)), 1312222064);
        }
        $this->exitCode = $exitCode;
    }

    /**
     * Gets the numerical exit code which should be returned when exiting this application.
     *
     * @return integer
     * @api
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * Sets color support / styled output to yes, no or auto detection
     *
     * @param boolean $colorSupport TRUE, FALSE or NULL (= autodetection)
     * @return void
     */
    public function setColorSupport($colorSupport)
    {
        $this->colorSupport = $colorSupport;
    }

    /**
     * Tells if the response content should be styled on send().
     *
     * Regardless of this setting content will only be styled with output format
     * set to "styled".
     *
     * @return boolean TRUE if the terminal support ANSI colors, otherwise FALSE
     */
    public function hasColorSupport()
    {
        if ($this->colorSupport !== null) {
            return $this->colorSupport;
        }
        if (DIRECTORY_SEPARATOR !== '\\') {
            return function_exists('posix_isatty') && posix_isatty(STDOUT);
        } else {
            return getenv('ANSICON') !== false;
        }
    }

    /**
     * Sets the desired output format.
     *
     * @param integer $outputFormat One of the OUTPUTFORMAT_* constants
     * @return void
     */
    public function setOutputFormat($outputFormat)
    {
        $this->outputFormat = $outputFormat;
    }

    /**
     * Returns the currently set output format.
     *
     * @return integer One of the OUTPUTFORMAT_* constants
     */
    public function getOutputFormat()
    {
        return $this->outputFormat;
    }

    /**
     * Sends the response
     *
     * @return void
     * @api
     */
    public function send()
    {
        if ($this->content === null) {
            return;
        }

        if ($this->outputFormat === self::OUTPUTFORMAT_RAW) {
            echo $this->content;
            return;
        }

        if ($this->outputFormat === self::OUTPUTFORMAT_PLAIN) {
            echo strip_tags($this->content);
            return;
        }

        $content = $this->getContent();
        if ($this->hasColorSupport() === true) {
            $content =  preg_replace('|\<b>(((?!\</b>).)*)\</b>|', "\x1B[" . self::STYLE_BRIGHT . "m\$1\x1B[0m", $content);
            $content =  preg_replace('|\<i>(((?!\</i>).)*)\</i>|', "\x1B[" . self::STYLE_ITALIC . "m\$1\x1B[0m", $content);
            $content =  preg_replace('|\<u>(((?!\</u>).)*)\</u>|', "\x1B[" . self::STYLE_UNDERLINED . "m\$1\x1B[0m", $content);
            $content =  preg_replace('|\<em>(((?!\</em>).)*)\</em>|', "\x1B[" . self::STYLE_INVERSE . "m\$1\x1B[0m", $content);
            $content =  preg_replace('|\<strike>(((?!\</strike>).)*)\</strike>|', "\x1B[" . self::STYLE_STRIKETHROUGH . "m\$1\x1B[0m", $content);
            $content =  preg_replace('|\<error>(((?!\</error>).)*)\</error>|', "\x1B[" . self::STYLE_ERROR . "m\$1\x1B[0m", $content);
            $content =  preg_replace('|\<success>(((?!\</success>).)*)\</success>|', "\x1B[" . self::STYLE_SUCCESS . "m\$1\x1B[0m", $content);
        } else {
            $content =  preg_replace('|\<b>(((?!\</b>).)*)\</b>|', "$1", $content);
            $content =  preg_replace('|\<i>(((?!\</i>).)*)\</i>|', "$1", $content);
            $content =  preg_replace('|\<u>(((?!\</u>).)*)\</u>|', "$1", $content);
            $content =  preg_replace('|\<em>(((?!\</em>).)*)\</em>|', "$1", $content);
            $content =  preg_replace('|\<strike>(((?!\</strike>).)*)\</strike>|', "$1", $content);
            $content =  preg_replace('|\<error>(((?!\</strike>).)*)\</error>|', "$1", $content);
            $content =  preg_replace('|\<success>(((?!\</strike>).)*)\</success>|', "$1", $content);
        }
        echo $content;
    }
}

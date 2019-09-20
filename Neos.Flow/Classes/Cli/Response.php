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
class Response
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
     * @var string
     */
    private $content = '';

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
    public function setExitCode(int $exitCode): void
    {
        $this->exitCode = $exitCode;
    }

    /**
     * Gets the numerical exit code which should be returned when exiting this application.
     *
     * @return integer
     * @api
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * Overrides and sets the content of the response
     *
     * @param string $content The response content
     * @return void
     * @api
     */
    public function setContent(string $content): void
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
    public function appendContent(string $content): void
    {
        $this->content .= $content;
    }

    /**
     * Returns the response content without sending it.
     *
     * @return string The response content
     * @api
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Sets color support / styled output to yes, no or auto detection
     *
     * @param boolean $colorSupport true, false or NULL (= autodetection)
     * @return void
     */
    public function setColorSupport(bool $colorSupport): void
    {
        $this->colorSupport = $colorSupport;
    }

    /**
     * Tells if the response content should be styled on send().
     *
     * Regardless of this setting content will only be styled with output format
     * set to "styled".
     *
     * @return boolean true if the terminal support ANSI colors, otherwise false
     */
    public function hasColorSupport(): bool
    {
        if ($this->colorSupport !== null) {
            return $this->colorSupport;
        }
        if (DIRECTORY_SEPARATOR !== '\\') {
            return function_exists('posix_isatty') && posix_isatty(STDOUT);
        }
        return getenv('ANSICON') !== false;
    }

    /**
     * Sets the desired output format.
     *
     * @param integer $outputFormat One of the OUTPUTFORMAT_* constants
     * @return void
     */
    public function setOutputFormat(int $outputFormat): void
    {
        $this->outputFormat = $outputFormat;
    }

    /**
     * Returns the currently set output format.
     *
     * @return integer One of the OUTPUTFORMAT_* constants
     */
    public function getOutputFormat(): int
    {
        return $this->outputFormat;
    }

    /**
     * Sends the response
     *
     * @return void
     * @api
     */
    public function send(): void
    {
        if ($this->content === '') {
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

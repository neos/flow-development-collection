<?php
namespace Neos\Flow\Log\Backend;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * Extended ANSI console backend with human friendly formatting
 *
 * @api
 */
class AnsiConsoleBackend extends ConsoleBackend
{
    const FG_BLACK = "\033[0;30m";
    const FG_WHITE = "\033[1;37m";
    const FG_GRAY = "\033[0;37m";
    const FG_BLUE = "\033[0;34m";
    const FG_CYAN = "\033[0;36m";
    const FG_YELLOW = "\033[1;33m";
    const FG_RED = "\033[0;31m";
    const FG_GREEN = "\033[0;32m";

    const BG_CYAN = "\033[46m";
    const BG_GREEN = "\033[42m";
    const BG_RED = "\033[41m";
    const BG_YELLOW = "\033[43m";
    const BG_WHITE = "\033[47m";

    const END = "\033[0m";

    /**
     * @var array
     */
    protected $tagFormats = [];

    /**
     * @var boolean
     */
    protected $disableAnsi = false;

    /**
     * Open the log backend
     *
     * Initializes tag formats.
     *
     * @return void
     */
    public function open()
    {
        parent::open();
        $this->tagFormats = [
            'emergency' => self::FG_BLACK . self::BG_RED . '|' . self::END,
            'alert' => self::FG_BLACK . self::BG_YELLOW . '|' . self::END,
            'critical' => self::FG_BLACK . self::BG_CYAN . '|' . self::END,
            'error' => self::FG_RED . '|' . self::END,
            'warning' => self::FG_YELLOW . '|' . self::END,
            'notice' => self::FG_WHITE . '|' . self::END,
            'info' => self::FG_GREEN . '|' . self::END,
            'debug' => self::FG_BLUE . '|' . self::END,
        ];
    }

    /**
     * Appends the given message along with the additional information into the log.
     *
     * @param string $message
     * @param integer $severity
     * @param array $additionalData
     * @param string $packageKey
     * @param string $className
     * @param string $methodName
     * @return void
     */
    public function append($message, $severity = LOG_INFO, $additionalData = null, $packageKey = null, $className = null, $methodName = null)
    {
        if ($severity > $this->severityThreshold) {
            return;
        }

        $severityName = strtolower(trim($this->severityLabels[$severity]));
        $output = '<' . $severityName. '>' . $message . '</' . $severityName . '>';

        $output = $this->formatOutput($output);

        if (is_resource($this->streamHandle)) {
            fputs($this->streamHandle, $output . PHP_EOL);
        }
    }

    /**
     * Apply ansi formatting to output according to tags
     *
     * @param string $output
     * @return string
     */
    protected function formatOutput($output)
    {
        $tagFormats = $this->tagFormats;
        $disableAnsi = $this->disableAnsi;
        do {
            $lastOutput = $output;
            $output = preg_replace_callback('|(<([^>]+?)>(.*?)</\2>)|s', function ($matches) use ($tagFormats, $disableAnsi) {
                $format = isset($tagFormats[$matches[2]]) ? $tagFormats[$matches[2]] : '|';
                if ($disableAnsi) {
                    return $matches[3];
                } else {
                    return str_replace('|', $matches[3], $format);
                }
            }, $output);
        } while ($lastOutput !== $output);
        return $output;
    }

    /**
     * @param boolean $disableAnsi
     */
    public function setDisableAnsi($disableAnsi)
    {
        $this->disableAnsi = $disableAnsi;
    }

    /**
     * @return boolean
     */
    public function getDisableAnsi()
    {
        return $this->disableAnsi;
    }
}

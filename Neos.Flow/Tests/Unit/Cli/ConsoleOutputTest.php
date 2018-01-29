<?php
namespace Neos\Flow\Tests\Unit\Cli;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Cli;
use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Test cases for the CLI console output
 */
class ConsoleOutputTest extends UnitTestCase
{
    /**
     * @var Cli\ConsoleOutput
     */
    private $consoleOutput;

    /**
     * @var StreamOutput
     */
    private $output;

    /**
     * @var ArrayInput
     */
    private $input;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->input = new ArrayInput(['test']);
        $this->output = new StreamOutput(fopen('php://memory', 'w', false));

        $this->consoleOutput = new ConsoleOutput();
        $this->consoleOutput->setOutput($this->output);
        $this->consoleOutput->setInput($this->input);
        $this->consoleOutput->getInput()->setInteractive(false);
    }

    /**
     * @test
     */
    public function outputSimpleOutput()
    {
        $string = 'simple output';
        $this->consoleOutput->output($string);
        $this->assertSame($string, $this->getDisplay());
    }

    /**
     * @test
     */
    public function questionWasAskedFallBackToDefaultAnswer()
    {
        $this->assertSame('Not Sure', $this->consoleOutput->ask('Enter your name', 'Not Sure'));
    }

    /**
     * @test
     */
    public function questionIsAskedAnswerIsNo()
    {
        $answer = $this->consoleOutput->ask('Is this a test?');
        \Neos\Flow\var_dump($this->getDisplay());

        $this->assertSame(false, $answer);
    }

    /**
     * @test
     */
    public function questionIsAskedAnswerIsYes()
    {
        $answer = $this->consoleOutput->askConfirmation('Are you lying?');
        $this->assertSame(true, $answer);
    }

    /**
     * @test
     */
    public function returnUserInput()
    {
        $answer = $this->consoleOutput->ask('Are you lying?');
        $this->assertSame(true, $answer);
    }

    /**
     * Gets the display returned by the last execution of the command.
     *
     * @param bool $normalize Whether to normalize end of lines to \n or not
     *
     * @return string The display
     */
    private function getDisplay($normalize = false)
    {
        rewind($this->output->getStream());

        $display = stream_get_contents($this->output->getStream());

        if ($normalize) {
            $display = str_replace(PHP_EOL, "\n", $display);
        }

        return $display;
    }
}

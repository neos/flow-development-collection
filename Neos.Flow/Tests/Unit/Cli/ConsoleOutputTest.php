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
use Symfony\Component\Console\Tester\CommandTester;

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
        $this->input = new ArrayInput([]);
        $this->answerNothing();
        $this->output = new StreamOutput(fopen('php://memory', 'w', false));

        $this->consoleOutput = new ConsoleOutput();
        $this->consoleOutput->setOutput($this->output);
        $this->consoleOutput->setInput($this->input);
    }

    /**
     * @test
     */
    public function questionIsAskedAnswerIsNo()
    {
        $this->answerNo();
        $answer = $this->consoleOutput->ask('Is this a test?');

        $this->assertSame('no', $answer);
    }

    /**
     * @test
     */
    public function questionIsAskedAnswerIsYes()
    {
        $this->answerYes();
        $answer = $this->consoleOutput->ask('Are you lying?');
        $this->assertSame('yes', $answer);
    }

    /**
     * @test
     */
    public function questionIsWrittenToOutput()
    {
        $this->answerYes();
        $this->consoleOutput->ask('Is this a test?');

        $this->assertSame('Is this a test?', $this->returnOutput());
    }

    /**
     * @test
     */
    public function outputSimpleOutput()
    {
        $string = 'simple output';
        $this->consoleOutput->output($string);
        $this->assertSame($string, $this->returnOutput());
    }

    /**
     * @test
     */
    public function questionWasAskedFallBackToDefaultAnswer()
    {
        $this->assertSame('Not Sure', $this->consoleOutput->ask('Enter your name', 'Not Sure'));
    }

    private function answerYes()
    {
        $this->input->setStream(self::createStream(['yes']));
    }

    private function answerNo()
    {
        $this->input->setStream(self::createStream(['no']));
    }

    private function answerCustom(string $answer)
    {
        $this->input->setStream(self::createStream([$answer]));
    }

    private function answerNothing()
    {
        $this->input->setStream(self::createStream([' ']));
    }

    /**
     * @param bool $normalize Add line breaks at the end
     * @return string $streamContent
     */
    private function returnOutput($normalize = false)
    {
        rewind($this->output->getStream());

        $streamContent = stream_get_contents($this->output->getStream());

        if ($normalize) {
            $streamContent = str_replace(PHP_EOL, "\n", $streamContent);
        }

        return $streamContent;
    }

    /**
     * @param array $inputs
     * @return bool|resource
     */
    private static function createStream(array $inputs)
    {
        $stream = fopen('php://memory', 'r+', false);

        fwrite($stream, implode(PHP_EOL, $inputs));
        rewind($stream);

        return $stream;
    }
}

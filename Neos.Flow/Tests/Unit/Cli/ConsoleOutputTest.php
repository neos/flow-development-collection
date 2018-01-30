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
 * Test cases for CLI console output helpers
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
    public function setUp(): void
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
    public function outputIsSimpleOutput()
    {
        $string = 'simple output';
        $this->consoleOutput->output($string);

        $this->assertSame($string, $this->getActualConsoleOutput());
    }

    /**
     * @test
     */
    public function outputIsLine()
    {
        $string = 'simple line';
        $this->consoleOutput->outputLine($string);

        $this->assertSame($string . PHP_EOL, $this->getActualConsoleOutput());
    }

    /**
     * @test
     */
    public function outputIsFormattedAndMaximumLineLengthIsObeyed()
    {
        $string =
            'this is ' . PHP_EOL .
            'mutliline ' . PHP_EOL .
            'content and one line is longer than 79 characters, it\'s exactly this line to be precice ';

        $this->consoleOutput->outputFormatted($string, [], 2);

        $formattedString =
            '  this is ' . PHP_EOL .
            '  mutliline ' . PHP_EOL .
            '  content and one line is longer than 79 characters, it\'s exactly this line to' . PHP_EOL .
            '  be precice ' . PHP_EOL;

        $this->assertSame($formattedString, $this->getActualConsoleOutput());
    }

    /**
     * @test
     */
    public function questionIsAskedAnswerIsNo()
    {
        $this->answerNo();
        $userAnswer = $this->consoleOutput->askConfirmation('Is this a test?');

        $this->assertSame(false, $userAnswer);
    }

    /**
     * @test
     */
    public function questionIsAskedAnswerIsYes()
    {
        $this->answerYes();
        $userAnswer = $this->consoleOutput->askConfirmation('Are you lying?');

        $this->assertSame(true, $userAnswer);
    }

    /**
     * @test
     */
    public function questionIsWrittenToOutput()
    {
        $this->answerYes();
        $this->consoleOutput->ask('Is this a test?');

        $this->assertSame('Is this a test?', $this->getActualConsoleOutput());
    }

    /**
     * @test
     */
    public function multiLineAnswerIsSplitIntoMultipleLines()
    {
        $this->answerYes();
        $this->consoleOutput->ask(['First line', 'Second line']);

        $this->assertSame('First line'.PHP_EOL.'Second line', $this->getActualConsoleOutput());
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
    public function tableCanBeDrawn()
    {
        $this->consoleOutput->outputTable([['column1', 'column2']], ['header 1', 'header 2']);

        $this->assertSame(
            '+----------+----------+' . PHP_EOL .
            '| header 1 | header 2 |' . PHP_EOL .
            '+----------+----------+' . PHP_EOL .
            '| column1  | column2  |' . PHP_EOL .
            '+----------+----------+' . PHP_EOL, $this->getActualConsoleOutput());
    }

    /**
     * @test
     */
    public function askAndValidate()
    {
        $this->answerCustom(5);
        $validator = function ($number) {
            return $number > 4;
        };

        $userAnswer = $this->consoleOutput->askAndValidate('Enter a number higher than 4', $validator);

        $this->assertSame(true, $userAnswer);
    }

    /**
     * @test
     */
    public function drawProgressBar()
    {
        $this->consoleOutput->progressStart(100);
        $this->consoleOutput->progressAdvance();
        $this->consoleOutput->progressSet(50);
        $this->consoleOutput->progressFinish();
        $this->assertSame(
            '   0/100 [>---------------------------]   0%' . PHP_EOL .
            '   1/100 [>---------------------------]   1%' . PHP_EOL .
            '  50/100 [==============>-------------]  50%' . PHP_EOL .
            ' 100/100 [============================] 100%', $this->getActualConsoleOutput());
    }

    /**
     * @test
     */
    public function selectAnChoosableeAnswer()
    {
        $this->answerCustom('no');
        $choices = [
            'yes' => 'No',
            'no' => 'Yes'
        ];
        $userAnswer = $this->consoleOutput->select('Is this a good test?', $choices, 'no', true);

        $this->assertSame(['no'], $userAnswer);
    }

    /**
     * @test
     */
    public function selectAnswerIsDisplayed()
    {
        $userAnswer = 1;
        $this->answerCustom($userAnswer);
        $choices = [
            1 => 'No',
            2 => 'Yes'
        ];
        $this->consoleOutput->select('Is this a good test?', $choices, 1, true);

        $this->assertSame(
            'Is this a good test?' . PHP_EOL .
            '  [1] No' . PHP_EOL .
            '  [2] Yes' . PHP_EOL .
            ' > ' . $userAnswer,
            $this->getActualConsoleOutput()
        );
    }

    private function answerYes(): void
    {
        $this->input->setStream(self::createStream(['yes']));
    }

    private function answerNo(): void
    {
        $this->input->setStream(self::createStream(['no']));
    }

    private function answerCustom(string $answer): void
    {
        $this->input->setStream(self::createStream([$answer]));
    }

    private function answerNothing(): void
    {
        $this->input->setStream(self::createStream([' ']));
    }

    /**
     * Return output the way it will be represented within the console
     *
     * @return string $streamContent
     * @param bool $removeControlCharacters
     * @return bool|string
     */
    private function getActualConsoleOutput(bool $removeControlCharacters = true)
    {
        rewind($this->output->getStream());
        $streamContent = stream_get_contents($this->output->getStream());

        // remove control characters for cursor manipulation
        if ($removeControlCharacters === true) {
            $cursorCommandCharacters = ["\u{001b}[K", "\u{001b}[1P", "\u{001b}[1X", "\u{001b}[1@", "\u{001b}[1L",  "\u{001b}[1M"];
            $streamContent = str_replace($cursorCommandCharacters, '', $streamContent);
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

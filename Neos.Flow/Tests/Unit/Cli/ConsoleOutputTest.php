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

use Neos\Flow\Cli\ConsoleOutput;
use Neos\Flow\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Exception\RuntimeException;

/**
 * Test cases for CLI console output helpers
 */
class ConsoleOutputTest extends UnitTestCase
{
    /**
     * @var ConsoleOutput
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
    protected function setUp(): void
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

        self::assertSame($string, $this->getActualConsoleOutput());
    }

    /**
     * @test
     */
    public function outputIsLine()
    {
        $string = 'simple line';
        $this->consoleOutput->outputLine($string);

        self::assertSame($string . PHP_EOL, $this->getActualConsoleOutput());
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

        self::assertSame($formattedString, $this->getActualConsoleOutput());
    }

    /**
     * @test
     */
    public function questionIsAskedAnswerIsNo()
    {
        $this->answerNo();
        $userAnswer = $this->consoleOutput->askConfirmation('Is this a test?');

        self::assertSame(false, $userAnswer);
    }

    /**
     * @test
     */
    public function questionIsAskedAnswerIsYes()
    {
        $this->answerYes();
        $userAnswer = $this->consoleOutput->askConfirmation('Are you lying?');

        self::assertSame(true, $userAnswer);
    }

    /**
     * @test
     */
    public function questionIsWrittenToOutput()
    {
        $this->answerYes();
        $this->consoleOutput->ask('Is this a test?');

        self::assertSame('Is this a test?', $this->getActualConsoleOutput());
    }

    /**
     * @test
     */
    public function multiLineAnswerIsSplitIntoMultipleLines()
    {
        $this->answerYes();
        $this->consoleOutput->ask(['First line', 'Second line']);

        self::assertSame('First line'.PHP_EOL.'Second line', $this->getActualConsoleOutput());
    }

    /**
     * @test
     */
    public function askAndValidateWillReturnAnswerIfValidationSuccessful()
    {
        $this->answerCustom(5);
        $validator = function ($answer) {
            if ($answer > 4) {
                return $answer;
            }

            throw new RuntimeException('Number is not higher than 4');
        };

        $userAnswer = $this->consoleOutput->askAndValidate('Enter a number higher than 4', $validator);

        self::assertSame('5', $userAnswer);
    }

    /**
     * @test
     */
    public function askAndValidateWillThrowExceptionIfNotSuccessful()
    {
        $this->expectException('RuntimeException');

        $this->answerCustom(5);
        $validator = function ($answer) {
            if ($answer > 6) {
                return $answer;
            }
            throw new RuntimeException('Number is not higher than 4');
        };

        $userAnswer = $this->consoleOutput->askAndValidate('Enter a number higher than 4', $validator);
    }

    /**
     * @test
     */
    public function questionWasAskedFallBackToDefaultAnswer()
    {
        self::assertSame('Not Sure', $this->consoleOutput->ask('Enter your name', 'Not Sure'));
    }

    /**
     * @test
     */
    public function tableCanBeDrawn()
    {
        $this->consoleOutput->outputTable([['column1', 'column2']], ['header 1', 'header 2']);

        self::assertSame(
            '+----------+----------+' . PHP_EOL .
            '| header 1 | header 2 |' . PHP_EOL .
            '+----------+----------+' . PHP_EOL .
            '| column1  | column2  |' . PHP_EOL .
            '+----------+----------+' . PHP_EOL,
            $this->getActualConsoleOutput()
        );
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
        self::assertSame(
            '   0/100 [>---------------------------]   0%' . PHP_EOL .
            '   1/100 [>---------------------------]   1%' . PHP_EOL .
            '  50/100 [==============>-------------]  50%' . PHP_EOL .
            ' 100/100 [============================] 100%',
            $this->getActualConsoleOutput()
        );
    }

    /**
     * @test
     */
    public function selectWithStringTypeChoiceKeys()
    {
        $this->answerCustom('y');
        $choices = [
            'n' => 'No',
            'y' => 'Yes'
        ];
        $userAnswer = $this->consoleOutput->select('Is this a good test?', $choices, 'yes', true);

        self::assertEquals(
            'Is this a good test?' . PHP_EOL .
            '  [n] No' . PHP_EOL .
            '  [y] Yes' . PHP_EOL .
            ' > y',
            $this->getActualConsoleOutput()
        );

        self::assertSame(['y'], $userAnswer, 'The answer is the key, NOT the value from the choices');
    }

    /**
     * @test
     */
    public function selectWithIntegerTypeChoiceKeys()
    {
        $givenAnswer = 2;
        $this->answerCustom($givenAnswer);
        $choices = [
            1 => 'No',
            2 => 'Yes'
        ];
        $userAnswer = $this->consoleOutput->select('Is this a good test?', $choices, 1, true);

        self::assertEquals(
            'Is this a good test?' . PHP_EOL .
            '  [1] No' . PHP_EOL .
            '  [2] Yes' . PHP_EOL .
            ' > ' . $givenAnswer,
            $this->getActualConsoleOutput()
        );

        self::assertSame(['Yes'], $userAnswer, 'The answer is the value, NOT the key from the choices');
    }

    /**
     * @test
     */
    public function selectTheDefaultWhenAnswerIsNothing()
    {
        $this->answerNothing();
        $choices = [
            'yes' => 'Yes',
            'no' => 'No'
        ];
        $userAnswer = $this->consoleOutput->select('Is this a good test?', $choices, 'yes', true);

        self::assertSame(['yes'], $userAnswer, 'The default value is returned');
    }

    /**
     * @return void
     */
    private function answerYes(): void
    {
        $this->input->setStream(self::createStream(['yes']));
    }

    /**
     * @return void
     */
    private function answerNo(): void
    {
        $this->input->setStream(self::createStream(['no']));
    }

    /**
     * @param string $answer A custom string equivalent to user input in console
     */
    private function answerCustom(string $answer): void
    {
        $this->input->setStream(self::createStream([$answer]));
    }

    /**
     * @return void
     */
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
            $cursorCommandCharacters = ["\u{001b}[K", "\u{001b}[1P", "\u{001b}[1X", "\u{001b}[1@", "\u{001b}[1L", "\u{001b}[1M", "\u{001b}7", "\u{001b}8"];
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

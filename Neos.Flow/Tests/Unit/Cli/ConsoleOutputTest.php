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
    public function outputSimpleOutput()
    {
        $string = 'simple output';
        $this->consoleOutput->output($string);

        $this->assertSame($string, $this->returnOutput());
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

        $this->assertSame('Is this a test?', $this->returnOutput());
    }

    /**
     * @test
     */
    public function multiLineAnswerIsSplitIntoMultipleLines()
    {
        $this->answerYes();
        $this->consoleOutput->ask(['First line', 'Second line']);

        $this->assertSame('First line'.PHP_EOL.'Second line', $this->returnOutput());
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
        $this->consoleOutput->outputTable([
            ['row1', 'row2'],
            ['header 1', 'header 2']
        ]);

        $this->assertSame(
            '+----------+----------+' . PHP_EOL .
            '| row1     | row2     |' . PHP_EOL .
            '| header 1 | header 2 |' . PHP_EOL .
            '+----------+----------+' . PHP_EOL, $this->returnOutput());
    }

    /**
     * @test
     */
    public function askAndValidate()
    {
        $this->answerCustom(5);
        $validator = function($number) {
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
        $progressBar = $this->consoleOutput->progressSet(50);
        $progressBar = $this->consoleOutput->progressFinish();
        $this->assertSame(
            '   0/100 [>---------------------------]   0%' . PHP_EOL .
            '   1/100 [>---------------------------]   1%' . PHP_EOL .
            '  50/100 [==============>-------------]  50%' . PHP_EOL .
            ' 100/100 [============================] 100%'
            , $this->returnOutput());
    }

    /**
     * @test
     */
    public function selectAndChoosableAnswer()
    {
        $this->answerCustom('no');
        $choices = [
            'yes' => 'No',
            'no' => 'Yes'
        ];
        $userAnswer = $this->consoleOutput->select('Is this a good test?', $choices, 2, true);

        $this->assertSame(['no'], $userAnswer);
    }

    /**
     * @test
     */
    public function selectAnswerIsDisplayed()
    {
        $this->answerCustom('1');
        $choices = [
            1 => 'No',
            2 => 'Yes'
        ];
        $this->consoleOutput->select('Is this a good test?', $choices, 2, true);

        $this->assertSame(
            'Is this a good test?' . PHP_EOL .
            '  [1] No' . PHP_EOL .
            '  [2] Yes' . PHP_EOL .' > 1',
            $this->returnOutput()
        );
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
     * @return string $streamContent
     */
    private function returnOutput()
    {
        rewind($this->output->getStream());
        $streamContent = stream_get_contents($this->output->getStream());

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

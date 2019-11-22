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

use Neos\Flow\Annotations as Flow;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;
use Symfony\Component\Console\Input\StringInput as SymfonyStringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * A wrapper for Symfony ConsoleOutput and related helpers
 */
class ConsoleOutput
{
    /**
     * @var SymfonyConsoleOutput
     */
    protected $output;

    /**
     * @var SymfonyStringInput
     */
    protected $input;

    /**
     * @var QuestionHelper
     */
    protected $questionHelper;

    /**
     * @var ProgressBar
     */
    protected $progressBar;

    /**
     * @var Table
     */
    protected $table;

    /**
     * Creates and initializes the SymfonyConsoleOutput instance
     */
    public function __construct()
    {
        $this->setOutput(new SymfonyConsoleOutput());
        $this->setInput(new SymfonyStringInput(''));
        $this->input->setInteractive(true);
        $this->output->getFormatter()->setStyle('b', new OutputFormatterStyle(null, null, ['bold']));
        $this->output->getFormatter()->setStyle('i', new OutputFormatterStyle('black', 'white'));
        $this->output->getFormatter()->setStyle('u', new OutputFormatterStyle(null, null, ['underscore']));
        $this->output->getFormatter()->setStyle('em', new OutputFormatterStyle(null, null, ['reverse']));
        $this->output->getFormatter()->setStyle('strike', new OutputFormatterStyle(null, null, ['conceal']));
        $this->output->getFormatter()->setStyle('error', new OutputFormatterStyle('red'));
        $this->output->getFormatter()->setStyle('success', new OutputFormatterStyle('green'));
    }

    /**
     * Returns the desired maximum line length for console output.
     *
     * @return integer
     */
    public function getMaximumLineLength(): int
    {
        return 79;
    }

    /**
     * Outputs specified text to the console window
     * You can specify arguments that will be passed to the text via sprintf
     * @see http://www.php.net/sprintf
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @return void
     */
    public function output(string $text, array $arguments = []): void
    {
        if ($arguments !== []) {
            $text = vsprintf($text, $arguments);
        }
        $this->output->write($text);
    }

    /**
     * Outputs specified text to the console window and appends a line break
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @return void
     * @see output()
     * @see outputLines()
     */
    public function outputLine(string $text = '', array $arguments = []): void
    {
        $this->output($text . PHP_EOL, $arguments);
    }

    /**
     * Formats the given text to fit into the maximum line length and outputs it to the
     * console window
     *
     * @param string $text Text to output
     * @param array $arguments Optional arguments to use for sprintf
     * @param integer $leftPadding The number of spaces to use for indentation
     * @return void
     * @see outputLine()
     */
    public function outputFormatted(string $text = '', array $arguments = [], int $leftPadding = 0): void
    {
        $lines = explode(PHP_EOL, $text);
        foreach ($lines as $line) {
            $formattedText = str_repeat(' ', $leftPadding) . wordwrap($line, $this->getMaximumLineLength() - $leftPadding, PHP_EOL . str_repeat(' ', $leftPadding), true);
            $this->outputLine($formattedText, $arguments);
        }
    }

    /**
     * Renders a table like output of the given $rows
     *
     * @param array $rows
     * @param array $headers
     */
    public function outputTable(array $rows, array $headers = null): void
    {
        $table = $this->getTable();
        if ($headers !== null) {
            $table->setHeaders($headers);
        }
        $table->setRows($rows);
        $table->render();
    }

    /**
     * Asks the user to select a value
     *
     * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param array $choices List of choices to pick from
     * @param mixed|null $default The default answer if the user enters nothing
     * @param boolean $multiSelect If true the result will be an array with the selected options. Multiple options can be given separated by commas
     * @param integer|null $attempts Max number of times to ask before giving up (null by default, which means infinite)
     * @return integer|string|array Either the value for indexed arrays, the key for associative arrays or an array for multiple selections
     * @throws \InvalidArgumentException
     */
    public function select($question, array $choices, $default = null, bool $multiSelect = false, int $attempts = null)
    {
        $question = new ChoiceQuestion($this->combineQuestion($question), $choices, $default);
        $question
            ->setMaxAttempts($attempts)
            ->setMultiselect($multiSelect)
            ->setErrorMessage('Value "%s" is invalid');

        return $this->getQuestionHelper()->ask($this->input, $this->output, $question);
    }

    /**
     * Asks a question to the user
     *
     * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param string $default The default answer if none is given by the user
     * @return mixed The user answer
     * @throws \RuntimeException If there is no data to read in the input stream
     */
    public function ask($question, string $default = null)
    {
        $question = new Question($this->combineQuestion($question), $default);

        return $this->getQuestionHelper()->ask($this->input, $this->output, $question);
    }

    /**
     * Asks a confirmation to the user.
     *
     * The question will be asked until the user answers by nothing, yes, or no.
     *
     * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param boolean $default The default answer if the user enters nothing
     * @return boolean true if the user has confirmed, false otherwise
     */
    public function askConfirmation($question, bool $default = true): bool
    {
        $question = new ConfirmationQuestion($this->combineQuestion($question), $default);

        return $this->getQuestionHelper()->ask($this->input, $this->output, $question);
    }

    /**
     * Asks a question to the user, the response is hidden
     *
     * @param string|array $question The question. If an array each array item is turned into one line of a multi-line question
     * @param Boolean $fallback In case the response can not be hidden, whether to fallback on non-hidden question or not
     * @return mixed The answer
     * @throws \RuntimeException In case the fallback is deactivated and the response can not be hidden
     */
    public function askHiddenResponse($question, bool $fallback = true)
    {
        $question = new Question($this->combineQuestion($question));
        $question
            ->setHidden(true)
            ->setHiddenFallback($fallback);

        return $this->getQuestionHelper()->ask($this->input, $this->output, $question);
    }

    /**
     * Asks for a value and validates the response
     *
     * The validator receives the data to validate. It must return the
     * validated data when the data is valid and throw an exception
     * otherwise.
     *
     * @see https://symfony.com/doc/current/components/console/helpers/questionhelper.html#validating-the-answer
     * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param callable $validator A PHP callback that gets a value and is expected to return the (transformed) value or throw an exception if it wasn't valid
     * @param integer|null $attempts Max number of times to ask before giving up (null by default, which means infinite)
     * @param string $default The default answer if none is given by the user
     * @return mixed The response
     * @throws \Exception When any of the validators return an error
     */
    public function askAndValidate($question, callable $validator, int $attempts = null, string $default = null)
    {
        $question = new Question($this->combineQuestion($question), $default);
        $question
            ->setValidator($validator)
            ->setMaxAttempts($attempts);

        return $this->getQuestionHelper()->ask($this->input, $this->output, $question);
    }

    /**
     * Asks for a value, hide and validates the response
     *
     * The validator receives the data to validate. It must return the
     * validated data when the data is valid and throw an exception
     * otherwise.
     *
     * @param string|array $question The question to ask. If an array each array item is turned into one line of a multi-line question
     * @param callable $validator A PHP callback that gets a value and is expected to return the (transformed) value or throw an exception if it wasn't valid
     * @param integer|null $attempts Max number of times to ask before giving up (null by default, which means infinite)
     * @param boolean $fallback In case the response can not be hidden, whether to fallback on non-hidden question or not
     * @return mixed The response
     * @throws \Exception When any of the validators return an error
     * @throws \RuntimeException In case the fallback is deactivated and the response can not be hidden
     */
    public function askHiddenResponseAndValidate($question, callable $validator, int $attempts = null, bool $fallback = true)
    {
        $question = new Question($this->combineQuestion($question));
        $question
            ->setHidden(true)
            ->setHiddenFallback($fallback)
            ->setValidator($validator)
            ->setMaxAttempts($attempts);

        return $this->getQuestionHelper()->ask($this->input, $this->output, $question);
    }

    /**
     * Starts the progress output
     *
     * @param integer $max Maximum steps. If NULL an indeterminate progress bar is rendered
     * @return void
     */
    public function progressStart(int $max = null): void
    {
        $this->getProgressBar()->start($max);
    }

    /**
     * Advances the progress output X steps
     *
     * @param integer $step Number of steps to advance
     * @return void
     * @throws \LogicException
     */
    public function progressAdvance(int $step = 1): void
    {
        $this->getProgressBar()->advance($step);
    }

    /**
     * Sets the current progress
     *
     * @param integer $current The current progress
     * @return void
     * @throws \LogicException
     */
    public function progressSet(int $current): void
    {
        $this->getProgressBar()->setProgress($current);
    }

    /**
     * Finishes the progress output
     *
     * @return void
     */
    public function progressFinish(): void
    {
        $this->getProgressBar()->finish();
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * @return OutputInterface
     */
    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    /**
     * @param InputInterface $input
     */
    public function setInput(InputInterface $input): void
    {
        $this->input = $input;
    }


    /**
     * @return InputInterface
     */
    public function getInput():InputInterface
    {
        return $this->input;
    }

    /**
     * Returns or initializes the symfony/console QuestionHelper
     *
     * @return QuestionHelper
     */
    protected function getQuestionHelper(): QuestionHelper
    {
        if ($this->questionHelper === null) {
            $this->questionHelper = new QuestionHelper();
            $helperSet = new HelperSet([new FormatterHelper()]);
            $this->questionHelper->setHelperSet($helperSet);
        }
        return $this->questionHelper;
    }

    /**
     * If question is an array, split it into multi-line string
     *
     * @param string|array $question
     * @return string
     */
    protected function combineQuestion($question): string
    {
        if (is_array($question)) {
            return implode(PHP_EOL, $question);
        }

        return $question;
    }

    /**
     * Returns or initializes the symfony/console ProgressHelper
     *
     * @return ProgressBar
     */
    protected function getProgressBar(): ProgressBar
    {
        if ($this->progressBar === null) {
            $this->progressBar = new ProgressBar($this->output);
            if (is_callable([$this->progressBar, 'minSecondsBetweenRedraws'])) {
                $this->progressBar->minSecondsBetweenRedraws(0);
            }
            if (is_callable([$this->progressBar, 'maxSecondsBetweenRedraws'])) {
                $this->progressBar->maxSecondsBetweenRedraws(0);
            }
        }
        return $this->progressBar;
    }

    /**
     * Returns or initializes the symfony/console Table
     *
     * @return Table
     */
    protected function getTable(): Table
    {
        if ($this->table === null) {
            $this->table = new Table($this->output);
        }
        return $this->table;
    }
}

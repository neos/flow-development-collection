<?php
namespace Neos\Flow\Core;

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
use Neos\Flow\Core\Booting\Exception\SubProcessException;
use Neos\Flow\Exception as FlowException;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Random\RandomException;
use JsonException;

/**
 * A handler for Flow CLI commands, to create CLI subrequests
 */
#[Flow\Proxy(false)]
final class PhpCliCommandHandler
{
    /** @internal only exposed for testing purposes, you should never set this. */
    private static string $builtPhpCommand;

    /**
     * No instance of this should be created
     */
    private function __construct()
    {
        // This is supposed to be used static only.
    }

    /**
     * Executes the given command as a sub-request to the Flow CLI system.
     *
     * @param string $commandIdentifier E.g. neos.flow:cache:flush
     * @param array<string, mixed> $settings The Neos.Flow settings
     * @param boolean $outputResults Echo the commands output on success
     * @param array<string, string> $commandArguments Command arguments
     * @return void
     * @throws FilesException
     * @throws FlowException
     * @throws SubProcessException The execution of the sub process failed
     * @throws JsonException
     * @throws RandomException
     * @api
     */
    public static function executeCommand(string $commandIdentifier, array $settings, bool $outputResults = true, array $commandArguments = []): void
    {
        $command = self::buildSubprocessCommand($commandIdentifier, $settings, $commandArguments);
        // Output errors in response
        $command .= ' 2>&1';
        $output = [];
        exec($command, $output, $result);
        if ($result === 0 && $outputResults) {
            echo implode(PHP_EOL, $output);
        }
        if ($result === 0) {
            return;
        }

        // The rest is error handling
        if (count($output) > 0) {
            $exceptionMessage = implode(PHP_EOL, $output);
        } else {
            $exceptionMessage = sprintf('Execution of subprocess failed with exit code %d without any further output. (Please check your PHP error log for possible Fatal errors)', $result);

            // If the command is too long, it'll just produce /usr/bin/php: Argument list too long but this will be invisible
            // If anything else goes wrong, it may as well not produce any $output, but might do so when run on an interactive
            // shell. Thus we dump the command next to the exception dumps.
            $exceptionMessage .= ' Try to run the command manually, to hopefully get some hint on the actual error.';
            if (!file_exists(FLOW_PATH_DATA . 'Logs/Exceptions')) {
                Files::createDirectoryRecursively(FLOW_PATH_DATA . 'Logs/Exceptions');
            }
            if (file_exists(FLOW_PATH_DATA . 'Logs/Exceptions') && is_dir(FLOW_PATH_DATA . 'Logs/Exceptions') && is_writable(FLOW_PATH_DATA . 'Logs/Exceptions')) {
                // Logs the command string `php ./flow foo:bar` inside `Logs/Exceptions/123-command.txt`
                $referenceCode = date('YmdHis', $_SERVER['REQUEST_TIME']) . bin2hex(random_bytes(3));
                $errorDumpPathAndFilename = FLOW_PATH_DATA . 'Logs/Exceptions/' . $referenceCode . '-command.txt';
                file_put_contents($errorDumpPathAndFilename, $command);
                $exceptionMessage .= sprintf(' It has been stored in: %s', basename($errorDumpPathAndFilename));
            } else {
                $exceptionMessage .= sprintf(' (could not write command into %s because the directory could not be created or is not writable.)', FLOW_PATH_DATA . 'Logs/Exceptions/');
            }
        }
        throw new SubProcessException($exceptionMessage, 1355480641);
    }

    /**
     * Executes the given command as a sub-request to the Flow CLI system without waiting for the output.
     *
     * Note: As the command execution is done in a separate thread potential exceptions or failures will *not* be reported
     *
     * @param string $commandIdentifier E.g. neos.flow:cache:flush
     * @param array<string, mixed> $settings The Neos.Flow settings
     * @param array<string, string> $commandArguments Command arguments
     * @return void
     * @throws FlowException
     * @throws SubProcessException
     * @throws JsonException
     * @api
     */
    public static function executeCommandAsync(string $commandIdentifier, array $settings, array $commandArguments = []): void
    {
        $command = self::buildSubprocessCommand($commandIdentifier, $settings, $commandArguments);
        if (DIRECTORY_SEPARATOR === '/') {
            exec($command . ' > /dev/null 2>/dev/null &');
        } else {
            pclose(popen('START /B CMD /S /C "' . $command . '" > NUL 2> NUL &', 'r'));
        }
    }

    /**
     * Build a subprocess command line string to be executed.
     *
     * @param string $commandIdentifier E.g. neos.flow:cache:flush
     * @param array<string, mixed> $settings The Neos.Flow settings
     * @param array<string, string> $commandArguments Command arguments
     * @return string A command line command ready for being exec()uted
     * @throws FlowException
     * @throws SubProcessException
     * @throws JsonException
     * @internal
     */
    public static function buildSubprocessCommand(string $commandIdentifier, array $settings, array $commandArguments = []): string
    {
        $command = self::buildAndValidatePhpCommand($settings);

        if (isset($settings['core']['subRequestIniEntries']) && is_array($settings['core']['subRequestIniEntries'])) {
            foreach ($settings['core']['subRequestIniEntries'] as $entry => $value) {
                $trimmedValue = trim($value);
                $command .= ' -d ' . escapeshellarg($entry) . ($trimmedValue !== '' ? ('=' . escapeshellarg($trimmedValue)) : '');
            }
        }

        $escapedArguments = '';
        foreach ($commandArguments as $argument => $argumentValue) {
            $argumentValue = trim($argumentValue);
            $escapedArguments .= ' ' . escapeshellarg('--' . trim($argument)) . ($argumentValue !== '' ? '=' . escapeshellarg($argumentValue) : '');
        }

        $command .= sprintf(' %s %s %s', escapeshellarg(FLOW_PATH_FLOW . 'Scripts/flow.php'), escapeshellarg($commandIdentifier), trim($escapedArguments));
        return trim($command);
    }

    /**
     * Provides a PHP CLI command string that calls PHP with the right configuration appended.
     * You probably want to use {@see self::buildAndValidatePhpCommand()}
     *
     * @param array<string, mixed> $settings The Neos.Flow settings
     * @return string A command line command for PHP, which can be extended and then exec()uted
     * @internal Exposed for testing, this will not validate if the php binary is available/correct nor its' version
     * @see self::buildAndValidatePhpCommand()
     */
    public static function buildPhpCommand(array $settings): string
    {
        $subRequestEnvironmentVariables = [
            'FLOW_ROOTPATH' => FLOW_PATH_ROOT,
            'FLOW_PATH_TEMPORARY_BASE' => FLOW_PATH_TEMPORARY_BASE,
            'FLOW_CONTEXT' => $settings['core']['context']
        ];
        if (isset($settings['core']['subRequestEnvironmentVariables'])) {
            $subRequestEnvironmentVariables = array_merge($subRequestEnvironmentVariables, $settings['core']['subRequestEnvironmentVariables']);
        }

        $command = '';
        foreach ($subRequestEnvironmentVariables as $argumentKey => $argumentValue) {
            if (DIRECTORY_SEPARATOR === '/') {
                $command .= sprintf('%s=%s ', $argumentKey, escapeshellarg($argumentValue));
            } else {
                // SET does not parse out quotes, hence we need escapeshellcmd here instead
                $command .= sprintf('SET %s=%s&', $argumentKey, escapeshellcmd($argumentValue));
            }
        }
        if (DIRECTORY_SEPARATOR === '/') {
            $phpBinaryPathAndFilename = '"' . escapeshellcmd(Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename'])) . '"';
        } else {
            $phpBinaryPathAndFilename = escapeshellarg(Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename']));
        }
        $command .= $phpBinaryPathAndFilename;
        if (!isset($settings['core']['subRequestPhpIniPathAndFilename']) || $settings['core']['subRequestPhpIniPathAndFilename'] !== false) {
            $useIniFile = $settings['core']['subRequestPhpIniPathAndFilename'] ?? php_ini_loaded_file();
            $command .= ' -c ' . escapeshellarg($useIniFile);
        }

        return $command;
    }

    /**
     * Provides a verified PHP CLI command string that calls PHP in the right version with all configuration.
     * To be appended with your individual call.
     *
     * @param array<string, mixed> $settings
     * @return string
     * @throws FlowException
     * @throws JsonException
     * @throws SubProcessException
     */
    public static function buildAndValidatePhpCommand(array $settings): string
    {
        if (isset(self::$builtPhpCommand)) {
            return self::$builtPhpCommand;
        }

        $command = self::buildPhpCommand($settings);
        self::ensureCLISubrequestsUseCurrentlyRunningPhpBinary($settings['core']['phpBinaryPathAndFilename']);
        self::ensureWebSubrequestsUseCurrentlyRunningPhpVersion($command);
        self::setBuildPhpCommand($command);
        return $command;
    }

    /**
     * @param string $commmand
     * @return void
     * @internal only used for testing, you should never set this.
     */
    public static function setBuildPhpCommand(string $commmand): void
    {
        self::$builtPhpCommand = $commmand;
    }

    /**
     * Compares the realpath of the configured PHP binary (if any) with the one flow was called with in a CLI request.
     * This avoids config errors where users forget to set Neos.Flow.core.phpBinaryPathAndFilename in CLI.
     *
     * @param string $phpBinaryPathAndFilename
     * @throws SubProcessException in case the php binary doesn't exist / is a different one for the current cli request
     */
    private static function ensureCLISubrequestsUseCurrentlyRunningPhpBinary(string $phpBinaryPathAndFilename = ''): void
    {
        // Do nothing for non-CLI requests
        if (PHP_SAPI !== 'cli') {
            return;
        }

        // Ensure the actual PHP binary is known before checking if it is correct.
        if ($phpBinaryPathAndFilename === '') {
            throw new SubProcessException('"Neos.Flow.core.phpBinaryPathAndFilename" is not set.', 1689676816060);
        }

        $command = [];
        if (PHP_OS_FAMILY !== 'Windows') {
            // Handle possible fast cgi: send empty stdin to close possible fast cgi server
            //
            // in case the phpBinaryPathAndFilename points to a fast cgi php binary we will get caught in an endless process
            // the fast cgi will expect input from the stdin and otherwise continue listening
            // to close the stdin we send an empty string
            // related https://bugs.php.net/bug.php?id=71209
            $command[] = 'echo "" | ';
        }
        $command[] = $phpBinaryPathAndFilename;
        $command[] = <<<'EOF'
        -r "echo realpath(PHP_BINARY);"
        EOF;
        $command[] = '2>&1'; // Output errors in response

        // Try to resolve which binary file PHP is pointing to
        $output = [];
        exec(implode(' ', $command), $output, $result);

        if ($result === 0 && count($output) === 1) {
            // Resolve any wrapper
            $configuredPhpBinaryPathAndFilename = $output[0];
        } else {
            // Resolve any symlinks that the configured php might be pointing to
            $configuredPhpBinaryPathAndFilename = realpath($phpBinaryPathAndFilename);
        }

        // if the configured PHP binary is empty here, the file does not exist.
        if ($configuredPhpBinaryPathAndFilename === false || $configuredPhpBinaryPathAndFilename === '') {
            throw new SubProcessException(
                sprintf('The configured PHP binary "%s" via setting the "Neos.Flow.core.phpBinaryPathAndFilename" doesnt exist.', $phpBinaryPathAndFilename),
                1689676923331
            );
        }

        // stfu to avoid possible open_basedir restriction https://github.com/neos/flow-development-collection/pull/2491
        $realPhpBinary = @realpath(PHP_BINARY);
        if ($realPhpBinary === false) {
            // bypass with exec open_basedir restriction
            $output = [];
            exec(PHP_BINARY . ' -r "echo realpath(PHP_BINARY);"', $output);
            $realPhpBinary = $output[0];
        }
        if (strcmp($realPhpBinary, $configuredPhpBinaryPathAndFilename) !== 0) {
            throw new SubProcessException(sprintf(
                'You are running the Flow CLI with a PHP binary different from the one Flow is configured to use internally. ' .
                'Flow has been run with "%s", while the PHP version Flow is configured to use for subrequests is "%s". Make sure to configure Flow to ' .
                'use the same PHP binary by setting the "Neos.Flow.core.phpBinaryPathAndFilename" configuration option to "%s". Flush the ' .
                'caches by removing the folder Data/Temporary before running ./flow again.',
                $realPhpBinary,
                $configuredPhpBinaryPathAndFilename,
                $realPhpBinary
            ), 1536303119);
        }
    }

    /**
     * Compares the actual version of the configured PHP binary (if any) with the one flow was called with in a non-CLI request.
     * This avoids config errors where users forget to set Neos.Flow.core.phpBinaryPathAndFilename in connection with a web
     * server.
     *
     * @param string $phpCommand the completely build php string that is used to execute subrequests
     * @throws FlowException
     * @throws SubProcessException in case the php binary doesn't exist, or is not suitable for cli usage, or its version doesn't match
     * @throws JsonException
     */
    private static function ensureWebSubrequestsUseCurrentlyRunningPhpVersion(string $phpCommand): void
    {
        // Do nothing for CLI requests
        if (PHP_SAPI === 'cli') {
            return;
        }

        $command = [];
        if (PHP_OS_FAMILY !== 'Windows') {
            // Handle possible fast cgi: send empty stdin to close possible fast cgi server
            //
            // in case the phpBinaryPathAndFilename points to a fast cgi php binary we will get caught in an endless process
            // the fast cgi will expect input from the stdin and otherwise continue listening
            // to close the stdin we send an empty string
            // related https://bugs.php.net/bug.php?id=71209
            $command[] = 'echo "" | ';
        }
        $command[] = $phpCommand;
        $command[] = <<<'EOF'
        -r "echo json_encode(['sapi' => PHP_SAPI, 'version' => PHP_VERSION]);"
        EOF;
        $command[] = '2>&1'; // Output errors in response

        exec(implode(' ', $command), $output, $result);

        $phpInformation = json_decode($output[0] ?? '{}', true, 512, JSON_THROW_ON_ERROR) ?: [];

        if ($result !== 0 || ($phpInformation['sapi'] ?? null) !== 'cli') {
            throw new SubProcessException(sprintf('PHP binary might not exist or is not suitable for cli usage. Command `%s` didnt succeed.', $phpCommand), 1689676967447);
        }

        /**
         * Checks if two (php) versions equal by comparing major and minor.
         * Differences in the patch level will be ignored.
         *
         * versionsAlmostEqual(8.1.0, 8.1.1) === true
         */
        $versionsAlmostEqual = static fn (string $oneVersion, string $otherVersion): bool =>
            array_slice(explode('.', $oneVersion), 0, 2) === array_slice(explode('.', $otherVersion), 0, 2);

        if (!$versionsAlmostEqual($phpInformation['version'], PHP_VERSION)) {
            throw new FlowException(sprintf(
                'You are executing Neos/Flow with a PHP version different from the one Flow is configured to use internally. ' .
                'Flow is running with with PHP "%s", while the PHP version Flow is configured to use for subrequests is "%s". Make sure to configure Flow to ' .
                'use the same PHP version by setting the "Neos.Flow.core.phpBinaryPathAndFilename" configuration option to a PHP-CLI binary of the version ' .
                '%s. Flush the caches by removing the folder Data/Temporary before executing Flow/Neos again.',
                PHP_VERSION,
                $phpInformation['version'],
                PHP_VERSION
            ), 1536563428);
        }
    }
}

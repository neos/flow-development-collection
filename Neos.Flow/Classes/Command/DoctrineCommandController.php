<?php
namespace Neos\Flow\Command;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Util\Debug;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Cli\Exception\StopCommandException;
use Neos\Flow\Error\Debugger;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Package;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Persistence\Doctrine\Service as DoctrineService;
use Neos\Flow\Utility\Exception as UtilityException;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Psr\Log\LoggerInterface;

/**
 * Command controller for tasks related to Doctrine
 *
 * @Flow\Scope("singleton")
 */
class DoctrineCommandController extends CommandController
{
    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @Flow\Inject
     * @var DoctrineService
     */
    protected $doctrineService;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var ThrowableStorageInterface
     */
    protected $throwableStorage;

    /**
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Injects the Flow settings, only the persistence part is kept for further use
     *
     * @param array $settings
     * @return void
     */
    public function injectSettings(array $settings): void
    {
        $this->settings = $settings['persistence'];
    }

    /**
     * @param LoggerInterface $logger
     */
    public function injectLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Compile the Doctrine proxy classes
     *
     * @return void
     * @throws UtilityException
     * @throws FilesException
     * @Flow\Internal
     */
    public function compileProxiesCommand(): void
    {
        $this->doctrineService->compileProxies();
    }

    /**
     * Validate the class/table mappings
     *
     * Checks if the current class model schema is valid. Any inconsistencies
     * in the relations between models (for example caused by wrong or
     * missing annotations) will be reported.
     *
     * Note that this does not check the table structure in the database in
     * any way.
     *
     * @return void
     * @throws StopCommandException
     * @see neos.flow:doctrine:entitystatus
     */
    public function validateCommand(): void
    {
        $this->outputLine();
        $classesAndErrors = $this->doctrineService->validateMapping();
        if (count($classesAndErrors) === 0) {
            $this->outputLine('Mapping validation passed, no errors were found.');
        } else {
            $this->outputLine('Mapping validation FAILED!');
            foreach ($classesAndErrors as $className => $errors) {
                $this->outputLine('  %s', [$className]);
                foreach ($errors as $errorMessage) {
                    $this->outputLine('    %s', [$errorMessage]);
                }
            }
            $this->quit(1);
        }
    }

    /**
     * Create the database schema
     *
     * Creates a new database schema based on the current mapping information.
     *
     * It expects the database to be empty, if tables that are to be created already
     * exist, this will lead to errors.
     *
     * @param string|null $output A file to write SQL to, instead of executing it
     * @return void
     * @throws ToolsException
     * @throws StopCommandException
     * @see neos.flow:doctrine:update
     * @see neos.flow:doctrine:migrate
     */
    public function createCommand(string $output = null): void
    {
        if (!$this->isDatabaseConfigured()) {
            $this->outputLine('Database schema creation has been SKIPPED, the driver and host backend options are not set in /Configuration/Settings.yaml.');
            $this->quit(1);
        }

        $this->doctrineService->createSchema($output);
        if ($output === null) {
            $this->outputLine('Created database schema.');
        } else {
            $this->outputLine('Wrote schema creation SQL to file "' . $output . '".');
        }
    }

    /**
     * Update the database schema
     *
     * Updates the database schema without using existing migrations.
     *
     * It will not drop foreign keys, sequences and tables, unless <u>--unsafe-mode</u> is set.
     *
     * @param boolean $unsafeMode If set, foreign keys, sequences and tables can potentially be dropped.
     * @param string|null $output A file to write SQL to, instead of executing the update directly
     * @return void
     * @throws StopCommandException
     * @see neos.flow:doctrine:create
     * @see neos.flow:doctrine:migrate
     */
    public function updateCommand(bool $unsafeMode = false, string $output = null): void
    {
        if (!$this->isDatabaseConfigured()) {
            $this->outputLine('Database schema update has been SKIPPED, the driver and host backend options are not set in /Configuration/Settings.yaml.');
            $this->quit(1);
        }

        $this->doctrineService->updateSchema(!$unsafeMode, $output);
        if ($output === null) {
            $this->outputLine('Executed a database schema update.');
        } else {
            $this->outputLine('Wrote schema update SQL to file "' . $output . '".');
        }
    }

    /**
     * Show the current status of entities and mappings
     *
     * Shows basic information about which entities exist and possibly if their
     * mapping information contains errors or not.
     *
     * To run a full validation, use the validate command.
     *
     * @param boolean $dumpMappingData If set, the mapping data will be output
     * @param string|null $entityClassName If given, the mapping data for just this class will be output
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @see neos.flow:doctrine:validate
     */
    public function entityStatusCommand(bool $dumpMappingData = false, string $entityClassName = null): void
    {
        $info = $this->doctrineService->getEntityStatus();

        if ($info === []) {
            $this->output('You do not have any mapped Doctrine ORM entities according to the current configuration. ');
            $this->outputLine('If you have entities or mapping files you should check your mapping configuration for errors.');
        } else {
            $this->outputLine('Found %d mapped entities:', [count($info)]);
            $this->outputLine();
            if ($entityClassName === null) {
                foreach ($info as $entityClassName => $entityStatus) {
                    if ($entityStatus instanceof ClassMetadata) {
                        $this->outputLine('<success>[OK]</success>   %s', [$entityClassName]);
                        if ($dumpMappingData) {
                            Debugger::clearState();
                            $this->outputLine(Debugger::renderDump($entityStatus, 0, true, true));
                        }
                    } else {
                        $this->outputLine('<error>[FAIL]</error> %s', [$entityClassName]);
                        $this->outputLine($entityStatus);
                        $this->outputLine();
                    }
                }
            } elseif (array_key_exists($entityClassName, $info) && $info[$entityClassName] instanceof ClassMetadata) {
                $entityStatus = $info[$entityClassName];
                $this->outputLine('<success>[OK]</success>   %s', [$entityClassName]);
                if ($dumpMappingData) {
                    Debugger::clearState();
                    $this->outputLine(Debugger::renderDump($entityStatus, 0, true, true));
                }
            } else {
                $this->outputLine('<info>[FAIL]</info> %s', [$entityClassName]);
                $this->outputLine('Class not found.');
                $this->outputLine();
            }
        }
    }

    /**
     * Run arbitrary DQL and display results
     *
     * Any DQL queries passed after the parameters will be executed, the results will be output:
     *
     * doctrine:dql --limit 10 'SELECT a FROM Neos\Flow\Security\Account a'
     *
     * @param integer $depth How many levels deep the result should be dumped
     * @param string $hydrationMode One of: object, array, scalar, single-scalar, simpleobject
     * @param integer|null $offset Offset the result by this number
     * @param integer|null $limit Limit the result to this number
     * @return void
     * @throws StopCommandException
     */
    public function dqlCommand(int $depth = 3, string $hydrationMode = 'array', int $offset = null, int $limit = null): void
    {
        if (!$this->isDatabaseConfigured()) {
            $this->outputLine('DQL query is not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
            $this->quit(1);
        }

        $dqlStatements = $this->request->getExceedingArguments();
        $hydrationModeConstant = 'Doctrine\ORM\Query::HYDRATE_' . strtoupper(str_replace('-', '_', $hydrationMode));
        if (!defined($hydrationModeConstant)) {
            throw new \InvalidArgumentException('Hydration mode "' . $hydrationMode . '" does not exist. It should be either: object, array, scalar or single-scalar.');
        }

        foreach ($dqlStatements as $dql) {
            $resultSet = $this->doctrineService->runDql($dql, constant($hydrationModeConstant), $offset, $limit);
            Debug::dump($resultSet, $depth);
        }
    }

    /**
     * Show the current migration status
     *
     * Displays the migration configuration as well as the number of
     * available, executed and pending migrations.
     *
     * @param boolean $showMigrations Output a list of all migrations and their status
     * @return void
     * @throws StopCommandException
     * @throws \Doctrine\DBAL\DBALException
     * @see neos.flow:doctrine:migrate
     * @see neos.flow:doctrine:migrationexecute
     * @see neos.flow:doctrine:migrationgenerate
     * @see neos.flow:doctrine:migrationversion
     */
    public function migrationStatusCommand(bool $showMigrations = false): void
    {
        if (!$this->isDatabaseConfigured()) {
            $this->outputLine('Doctrine migration status not available, the driver and host backend options are not set in /Configuration/Settings.yaml.');
            $this->quit(1);
        }

        $this->outputLine($this->doctrineService->getFormattedMigrationStatus($showMigrations));
    }

    /**
     * Migrate the database schema
     *
     * Adjusts the database structure by applying the pending
     * migrations provided by currently active packages.
     *
     * @param string $version The version to migrate to
     * @param string|null $output A file to write SQL to, instead of executing it
     * @param boolean $dryRun Whether to do a dry run or not
     * @param boolean $quiet If set, only the executed migration versions will be output, one per line
     * @return void
     * @throws StopCommandException
     * @see neos.flow:doctrine:migrationstatus
     * @see neos.flow:doctrine:migrationexecute
     * @see neos.flow:doctrine:migrationgenerate
     * @see neos.flow:doctrine:migrationversion
     */
    public function migrateCommand(string $version = 'latest', string $output = null, bool $dryRun = false, bool $quiet = false): void
    {
        if (!$this->isDatabaseConfigured()) {
            $this->outputLine('Doctrine migration not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
            $this->quit(1);
        }

        if (is_string($output) && !is_writable(dirname($output))) {
            $this->outputLine(sprintf('The path "%s" is not writeable!', dirname($output)));
        }

        try {
            $result = $this->doctrineService->executeMigrations($this->normalizeVersion($version), $output, $dryRun, $quiet);
            if ($result !== '' && $quiet === false) {
                $this->outputLine($result);
            }

            $this->emitAfterDatabaseMigration();
        } catch (\Exception $exception) {
            $this->handleException($exception);
        }
    }

    /**
     * @return void
     * @Flow\Signal
     */
    protected function emitAfterDatabaseMigration(): void
    {
    }

    /**
     * Execute a single migration
     *
     * Manually runs a single migration in the given direction.
     *
     * @param string $version The migration to execute
     * @param string $direction Whether to execute the migration up (default) or down
     * @param string|null $output A file to write SQL to, instead of executing it
     * @param boolean $dryRun Whether to do a dry run or not
     * @return void
     * @throws StopCommandException
     * @see neos.flow:doctrine:migrate
     * @see neos.flow:doctrine:migrationstatus
     * @see neos.flow:doctrine:migrationgenerate
     * @see neos.flow:doctrine:migrationversion
     */
    public function migrationExecuteCommand(string $version, string $direction = 'up', string $output = null, bool $dryRun = false): void
    {
        if (!$this->isDatabaseConfigured()) {
            $this->outputLine('Doctrine migration not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
            $this->quit(1);
        }

        if (is_string($output) && !is_writable(dirname($output))) {
            $this->outputLine(sprintf('The path "%s" is not writeable!', dirname($output)));
        }

        try {
            $this->outputLine($this->doctrineService->executeMigration($this->normalizeVersion($version), $direction, $output, $dryRun));
        } catch (\Exception $exception) {
            $this->handleException($exception);
        }
    }

    /**
     * Mark/unmark migrations as migrated
     *
     * If <u>all</u> is given as version, all available migrations are marked
     * as requested.
     *
     * @param string $version The migration to execute
     * @param boolean $add The migration to mark as migrated
     * @param boolean $delete The migration to mark as not migrated
     * @return void
     * @throws StopCommandException
     * @throws \Doctrine\DBAL\Exception
     * @see neos.flow:doctrine:migrate
     * @see neos.flow:doctrine:migrationstatus
     * @see neos.flow:doctrine:migrationexecute
     * @see neos.flow:doctrine:migrationgenerate
     */
    public function migrationVersionCommand(string $version, bool $add = false, bool $delete = false): void
    {
        if (!$this->isDatabaseConfigured()) {
            $this->outputLine('Doctrine migration not possible, the driver and host backend options are not set in /Configuration/Settings.yaml.');
            $this->quit(1);
        }

        if ($add === false && $delete === false) {
            throw new \InvalidArgumentException('You must specify whether you want to --add or --delete the specified version.');
        }

        try {
            $this->doctrineService->markAsMigrated($this->normalizeVersion($version), $add ?: false);
        } catch (MigrationException $exception) {
            $this->outputLine($exception->getMessage());
            $this->quit(1);
        }
    }

    /**
     * Generate a new migration
     *
     * If $diffAgainstCurrent is true (the default), it generates a migration file
     * with the diff between current DB structure and the found mapping metadata.
     *
     * Otherwise an empty migration skeleton is generated.
     *
     * Only includes tables/sequences matching the $filterExpression regexp when
     * diffing models and existing schema. Include delimiters in the expression!
     * The use of
     *
     *  --filter-expression '/^acme_com/'
     *
     * would only create a migration touching tables starting with "acme_com".
     *
     * Note: A filter-expression will overrule any filter configured through the
     * Neos.Flow.persistence.doctrine.migrations.ignoredTables setting
     *
     * @param boolean $diffAgainstCurrent Whether to base the migration on the current schema structure
     * @param string|null $filterExpression Only include tables/sequences matching the filter expression regexp
     * @param boolean $force Generate migrations even if there are migrations left to execute
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     * @throws StopCommandException
     * @throws FilesException
     * @see neos.flow:doctrine:migrate
     * @see neos.flow:doctrine:migrationstatus
     * @see neos.flow:doctrine:migrationexecute
     * @see neos.flow:doctrine:migrationversion
     */
    public function migrationGenerateCommand(bool $diffAgainstCurrent = true, string $filterExpression = null, bool $force = false): void
    {
        if (!$this->isDatabaseConfigured()) {
            $this->outputLine('Doctrine migration generation has been SKIPPED, the driver and host backend options are not set in /Configuration/Settings.yaml.');
            $this->quit(1);
        }

        $migrationStatus = $this->doctrineService->getMigrationStatus();
        if ($force === false && $migrationStatus['new'] !== 0) {
            $this->outputLine('There are %d new migrations available. To avoid duplication those should be executed via `doctrine:migrate` before creating additional migrations.', [$migrationStatus['new']]);
            $this->quit(1);
        }

        if ($migrationStatus['unavailable'] !== 0) {
            $this->outputLine('You have %d previously executed migrations in the database that are not registered migrations.', [$migrationStatus['unavailable']]);
        }

        // use default filter expression from settings
        if ($filterExpression === null) {
            $ignoredTables = array_keys(array_filter($this->settings['doctrine']['migrations']['ignoredTables']));
            if ($ignoredTables !== []) {
                $filterExpression = sprintf('/^(?!%s$).*$/xs', implode('$|', $ignoredTables));
            }
        }

        [$status, $migrationClassPathAndFilename] = $this->doctrineService->generateMigration($diffAgainstCurrent, $filterExpression);

        $this->outputLine('<info>%s</info>', [$status]);
        $this->outputLine();
        if ($migrationClassPathAndFilename) {
            $choices = ['Don\'t Move'];
            $packages = [];

            /** @var Package $package */
            foreach ($this->packageManager->getAvailablePackages() as $package) {
                $type = $package->getComposerManifest('type');
                if ($type === null || !is_string($type) || (strpos($type, 'typo3-') !== 0 && strpos($type, 'neos-') !== 0)) {
                    continue;
                }
                $choices[] = $package->getPackageKey();
                $packages[$package->getPackageKey()] = $package;
            }

            $selectedPackage = $this->output->select('Do you want to move the migration to one of these packages?', $choices, $choices[0]);
            $this->outputLine();

            if ($selectedPackage !== $choices[0]) {
                /** @var Package $selectedPackage */
                $selectedPackage = $packages[$selectedPackage];
                $targetPathAndFilename = Files::concatenatePaths([$selectedPackage->getPackagePath(), 'Migrations', $this->doctrineService->getDatabasePlatformName(), basename($migrationClassPathAndFilename)]);
                Files::createDirectoryRecursively(dirname($targetPathAndFilename));
                rename($migrationClassPathAndFilename, $targetPathAndFilename);
                $this->outputLine('The migration was moved to: <comment>%s</comment>', [substr($targetPathAndFilename, strlen(FLOW_PATH_ROOT))]);
                $this->outputLine();
                $this->outputLine('Next Steps:');
            } else {
                $this->outputLine('Next Steps:');
                $this->outputLine(sprintf('- Move <comment>%s</comment> to YourPackage/<comment>Migrations/%s/</comment>', $migrationClassPathAndFilename, $this->doctrineService->getDatabasePlatformName()));
            }
            $this->outputLine('- Review and adjust the generated migration.');
            $this->outputLine('- (optional) execute the migration using <comment>%s doctrine:migrate</comment>', [$this->getFlowInvocationString()]);
        }
    }

    /**
     * Output an error message and log the exception.
     *
     * @param \Exception $exception
     * @return void
     * @throws StopCommandException
     */
    protected function handleException(\Exception $exception): void
    {
        $this->outputLine('<error>%s</error>', [$exception->getMessage()]);
        $this->outputLine();
        $this->outputLine('The exception details have been logged to the Flow system log.');
        $message = $this->throwableStorage->logThrowable($exception);
        $this->outputLine($message);
        $this->logger->error($message, LogEnvironment::fromMethodName(__METHOD__));
        $this->quit(1);
    }

    protected function isDatabaseConfigured(): bool
    {
        // "driver" is used only for Doctrine, thus we (mis-)use it here
        return !($this->settings['backendOptions']['driver'] === null);
    }

    /**
     * Migrates a numeric version like "12345678901234" to a fully qualified version "Neos\Flow\Persistence\Doctrine\Migrations\Version12345678901234"
     *
     * @param string $version
     * @return string To fully qualified version including PHP namespace
     */
    private function normalizeVersion(string $version): string
    {
        if (!is_numeric($version)) {
            return $version;
        }
        return sprintf('Neos\Flow\Persistence\Doctrine\Migrations\Version%s', $version);
    }
}

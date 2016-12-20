<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Files;

/**
 * The central hub of the code migration tool in Flow.
 */
class Manager
{
    const STATE_NOT_MIGRATED = 0;
    const STATE_MIGRATED = 1;

    const EVENT_MIGRATION_START = 'migrationStart';
    const EVENT_MIGRATION_EXECUTE = 'migrationExecute';
    const EVENT_MIGRATION_SKIPPED = 'migrationSkipped';
    const EVENT_MIGRATION_ALREADY_APPLIED = 'migrationAlreadyApplied';
    const EVENT_MIGRATION_EXECUTED = 'migrationExecuted';
    const EVENT_MIGRATION_COMMITTED = 'migrationCommitted';
    const EVENT_MIGRATION_COMMIT_SKIPPED = 'commitSkipped';
    const EVENT_MIGRATION_DONE = 'migrationDone';
    const EVENT_MIGRATION_LOG_IMPORTED = 'migrationLogImported';

    /**
     * @var string
     */
    protected $packagesPath = FLOW_PATH_PACKAGES;

    /**
     * @var array
     */
    protected $packagesData = null;

    /**
     * The currently iterated package data (package key, composer manifest, ...)
     *
     * @var array
     */
    protected $currentPackageData = null;

    /**
     * @var AbstractMigration[]
     */
    protected $migrations = null;

    /**
     * Callbacks to be invoked when an event is triggered
     *
     * @see triggerEvent()
     * @var array
     */
    protected $eventCallbacks;

    /**
     * Allows to set the packages path.
     *
     * The level directly inside is expected to consist of package "categories"
     * (Framework, Application, Plugins, ...).
     *
     * @param string $packagesPath
     * @return void
     */
    public function setPackagesPath($packagesPath)
    {
        $this->packagesPath = $packagesPath;
    }

    /**
     * @return string
     */
    public function getCurrentPackageKey()
    {
        return isset($this->currentPackageData) ? $this->currentPackageData['packageKey'] : null;
    }

    /**
     * Returns the migration status for all packages.
     *
     * @param string $packageKey key of the package to fetch the migration status for
     * @param string $versionNumber version of the migration to fetch the status for (e.g. "20120126163610"), or NULL to consider all migrations
     * @return array in the format [<versionNumber> => ['migration' => <AbstractMigration>, 'state' => <STATE_*>], [...]]
     */
    public function getStatus($packageKey, $versionNumber = null)
    {
        $status = array();
        $migrations = $this->getMigrations($versionNumber);
        foreach ($this->getPackagesData($packageKey) as &$this->currentPackageData) {
            $packageStatus = array();
            foreach ($migrations as $migration) {
                if ($this->hasMigrationApplied($migration)) {
                    $state = self::STATE_MIGRATED;
                } else {
                    $state = self::STATE_NOT_MIGRATED;
                }
                $packageStatus[$migration->getVersionNumber()] = array('migration' => $migration, 'state' => $state);
            }
            $status[$this->currentPackageData['packageKey']] = $packageStatus;
        }
        return $status;
    }

    /**
     * This iterates over available migrations and applies them to
     * the existing packages if
     * - the package needs the migration
     * - is a clean git working copy
     *
     * @param string $packageKey key of the package to migrate
     * @param string $versionNumber version of the migration to execute (e.g. "20120126163610"), or NULL to execute all migrations
     * @param boolean $force if TRUE migrations will be applied even if the corresponding package is not a git working copy or contains local changes
     * @return void
     */
    public function migrate($packageKey, $versionNumber = null, $force = false)
    {
        $packagesData = $this->getPackagesData($packageKey);
        foreach ($this->getMigrations($versionNumber) as $migration) {
            $this->triggerEvent(self::EVENT_MIGRATION_START, array($migration));
            foreach ($packagesData as &$this->currentPackageData) {
                $this->migratePackage($migration, $force);
            }
            $this->triggerEvent(self::EVENT_MIGRATION_DONE, array($migration));
        }
    }

    /**
     * Apply the given migration to the package and commit the result.
     *
     * @param AbstractMigration $migration
     * @param boolean $force if TRUE the migration will be applied even if the current package is not a git working copy or contains local changes
     * @return void
     * @throws \RuntimeException
     */
    protected function migratePackage(AbstractMigration $migration, $force = false)
    {
        $packagePath = $this->currentPackageData['path'];
        if ($this->hasMigrationApplied($migration)) {
            $this->triggerEvent(self::EVENT_MIGRATION_ALREADY_APPLIED, array($migration, 'Migration already applied'));
            return;
        }
        $isWorkingCopy = Git::isWorkingCopy($packagePath);
        $hasLocalChanges = Git::isWorkingCopyDirty($packagePath);
        if (!$force) {
            if (!$isWorkingCopy) {
                $this->triggerEvent(self::EVENT_MIGRATION_SKIPPED, array($migration, 'Not a Git working copy, use --force to apply changes anyways'));
                return;
            }
            if ($hasLocalChanges) {
                $this->triggerEvent(self::EVENT_MIGRATION_SKIPPED, array($migration, 'Working copy contains local changes, use --force to apply changes anyways'));
                return;
            }
        }

        if ($isWorkingCopy) {
            $importResult = $this->importMigrationLogFromGitHistory(!$hasLocalChanges);
            if ($importResult !== null) {
                $this->triggerEvent(self::EVENT_MIGRATION_LOG_IMPORTED, array($migration, $importResult));
            }
        }

        $this->triggerEvent(self::EVENT_MIGRATION_EXECUTE, array($migration));
        try {
            $migration->prepare($this->currentPackageData);
            $migration->up();
            $migration->execute();
            $commitMessageNotice = null;
            if ($isWorkingCopy && !Git::isWorkingCopyDirty($packagePath)) {
                $commitMessageNotice = 'Note: This migration did not produce any changes, so the commit simply marks the migration as applied. This makes sure it will not be applied again.';
            }
            $this->markMigrationApplied($migration);
            $this->triggerEvent(self::EVENT_MIGRATION_EXECUTED, array($migration));
            if ($hasLocalChanges || !$isWorkingCopy) {
                $this->triggerEvent(self::EVENT_MIGRATION_COMMIT_SKIPPED, array($migration, $hasLocalChanges ? 'Working copy contains local changes' : 'No Git working copy'));
            } else {
                $migrationResult = $this->commitMigration($migration, $commitMessageNotice);
                $this->triggerEvent(self::EVENT_MIGRATION_COMMITTED, array($migration, $migrationResult));
            }
        } catch (\Exception $exception) {
            throw new \RuntimeException(sprintf('Applying migration "%s" to "%s" failed: "%s"', $migration->getIdentifier(), $this->currentPackageData['packageKey'], $exception->getMessage()), 1421692982, $exception);
        }
    }

    /**
     * Whether or not the given $migration has been applied to the current package
     *
     * @param AbstractMigration $migration
     * @return boolean
     */
    protected function hasMigrationApplied(AbstractMigration $migration)
    {
        // if the "applied-flow-migrations" section doesn't exist, we fall back to checking the git log for applied migrations for backwards compatibility
        if (!isset($this->currentPackageData['composerManifest']['extra']['applied-flow-migrations'])) {
            return Git::logContains($this->currentPackageData['path'], 'Migration: ' . $migration->getIdentifier());
        }
        return in_array($migration->getIdentifier(), $this->currentPackageData['composerManifest']['extra']['applied-flow-migrations']);
    }

    /**
     * @return void
     */
    protected function writeComposerManifest()
    {
        $composerFilePathAndName = Files::concatenatePaths([$this->currentPackageData['path'], 'composer.json']);
        file_put_contents($composerFilePathAndName, json_encode($this->currentPackageData['composerManifest'], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Imports the core migration log from the git history if it has not been imported previously (the "applied-flow-migrations" composer manifest property does not exist)
     *
     * @param boolean $commitChanges if TRUE the modified composer manifest is committed - if it changed
     * @return string
     */
    protected function importMigrationLogFromGitHistory($commitChanges = false)
    {
        if (isset($this->currentPackageData['composerManifest']['extra']['applied-flow-migrations'])) {
            return null;
        }
        $migrationCommitMessages = Git::getLog($this->currentPackageData['path'], 'Migration:');
        $appliedMigrationIdentifiers = [];
        foreach ($migrationCommitMessages as $commitMessage) {
            if (preg_match('/^\s*Migration\:\s?([^\s]*)/', $commitMessage, $matches) === 1) {
                $appliedMigrationIdentifiers[] = $matches[1];
            }
        }
        if ($appliedMigrationIdentifiers === array()) {
            return null;
        }
        if (!isset($this->currentPackageData['composerManifest']['extra'])) {
            $this->currentPackageData['composerManifest']['extra'] = [];
        }
        $this->currentPackageData['composerManifest']['extra']['applied-flow-migrations'] = array_unique($appliedMigrationIdentifiers);
        $this->writeComposerManifest();

        $this->currentPackageData['composerManifest']['extra']['applied-flow-migrations'] = array_values(array_unique($appliedMigrationIdentifiers));
        $composerFilePathAndName = Files::concatenatePaths([$this->currentPackageData['path'], 'composer.json']);
        Tools::writeComposerManifest($this->currentPackageData['composerManifest'], $composerFilePathAndName);

        if ($commitChanges) {
            $commitMessageSubject = 'TASK: Import core migration log to composer.json';
            if (!Git::isWorkingCopyRoot($this->currentPackageData['path'])) {
                $commitMessageSubject .= sprintf(' of "%s"', $this->currentPackageData['packageKey']);
            }

            $commitMessage = $commitMessageSubject . chr(10) . chr(10);
            $commitMessage .= wordwrap('This commit imports the core migration log to the "extra" section of the composer manifest.', 72);

            list($returnCode, $output) = Git::commitAll($this->currentPackageData['path'], $commitMessage);
            if ($returnCode === 0) {
                return '    ' . implode(PHP_EOL . '    ', $output) . PHP_EOL;
            } else {
                return '    No changes were committed.' . PHP_EOL;
            }
        }
    }

    /**
     * Whether or not the given migration has been applied in the given path
     *
     * @param AbstractMigration $migration
     * @return boolean
     */
    protected function markMigrationApplied(AbstractMigration $migration)
    {
        if (!isset($this->currentPackageData['composerManifest']['extra']['applied-flow-migrations'])) {
            $this->currentPackageData['composerManifest']['extra']['applied-flow-migrations'] = [];
        }
        $this->currentPackageData['composerManifest']['extra']['applied-flow-migrations'][] = $migration->getIdentifier();
        $composerFilePathAndName = Files::concatenatePaths([$this->currentPackageData['path'], 'composer.json']);
        Tools::writeComposerManifest($this->currentPackageData['composerManifest'], $composerFilePathAndName);
    }

    /**
     * Commit changes done to the package described by $packageData. The migration
     * that was did the changes is given with $versionNumber and $versionPackageKey
     * and will be recorded in the commit message.
     *
     * @param AbstractMigration $migration
     * @param string $commitMessageNotice
     * @return string
     */
    protected function commitMigration(AbstractMigration $migration, $commitMessageNotice = null)
    {
        $migrationIdentifier = $migration->getIdentifier();
        $commitMessageSubject = sprintf('TASK: Apply migration %s', $migrationIdentifier);
        if (!Git::isWorkingCopyRoot($this->currentPackageData['path'])) {
            $commitMessageSubject .= sprintf(' to package "%s"', $this->currentPackageData['packageKey']);
        }
        $commitMessage = $commitMessageSubject . chr(10) . chr(10);
        $description = $migration->getDescription();
        if ($description !== null) {
            $commitMessage .= wordwrap($description, 72);
        } else {
            $commitMessage .= wordwrap(sprintf('This commit contains the result of applying migration %s to this package.', $migrationIdentifier), 72);
        }

        if ($commitMessageNotice !== null) {
            $commitMessage .=  chr(10) . chr(10) . wordwrap($commitMessageNotice, 72) . chr(10) . chr(10);
        }

        list($returnCode, $output) = Git::commitAll($this->currentPackageData['path'], $commitMessage);
        if ($returnCode === 0) {
            return '    ' . implode(PHP_EOL . '    ', $output) . PHP_EOL;
        } else {
            return '    No changes were committed.' . PHP_EOL;
        }
    }

    /**
     * Attaches a new event handler
     *
     * @param string $eventIdentifier one of the EVENT_* constants
     * @param \Closure $callback a closure to be invoked when the corresponding event was triggered
     */
    public function on($eventIdentifier, \Closure $callback)
    {
        $this->eventCallbacks[$eventIdentifier][] = $callback;
    }

    /**
     * Trigger a custom event
     *
     * @param string $eventIdentifier one of the EVENT_* constants
     * @param array $eventData optional arguments to be passed to the handler closure
     */
    protected function triggerEvent($eventIdentifier, array $eventData = null)
    {
        if (!isset($this->eventCallbacks[$eventIdentifier])) {
            return;
        }
        /** @var \Closure $callback */
        foreach ($this->eventCallbacks[$eventIdentifier] as $callback) {
            call_user_func_array($callback, $eventData);
        }
    }


    /**
     * Initialize the manager: read package information and register migrations.
     *
     * @return void
     */
    protected function initialize()
    {
        if ($this->packagesData !== null) {
            return;
        }
        $this->packagesData = Tools::getPackagesData($this->packagesPath);

        $this->migrations = array();
        foreach ($this->packagesData as $packageKey => $packageData) {
            $this->registerMigrationFiles(Files::concatenatePaths(array($this->packagesPath, $packageData['category'], $packageKey)));
        }
        ksort($this->migrations);
    }

    /**
     * Look for code migration files in the given package path and register them
     * for further action.
     *
     * @param string $packagePath
     * @return void
     */
    protected function registerMigrationFiles($packagePath)
    {
        $packagePath = rtrim($packagePath, '/');
        $packageKey = substr($packagePath, strrpos($packagePath, '/') + 1);
        $migrationsDirectory = Files::concatenatePaths(array($packagePath, 'Migrations/Code'));
        if (!is_dir($migrationsDirectory)) {
            return;
        }

        foreach (Files::getRecursiveDirectoryGenerator($migrationsDirectory, '.php') as $filenameAndPath) {
            /** @noinspection PhpIncludeInspection */
            require_once($filenameAndPath);
            $baseFilename = basename($filenameAndPath, '.php');
            $className = '\\Neos\\Flow\\Core\\Migrations\\' . $baseFilename;
            /** @var AbstractMigration $migration */
            $migration = new $className($this, $packageKey);
            $this->migrations[$migration->getVersionNumber()] = $migration;
        }
    }


    /**
     * @param string $versionNumber if specified only the migration with the specified version is returned
     * @return AbstractMigration[]
     * @throws \InvalidArgumentException
     */
    protected function getMigrations($versionNumber = null)
    {
        $this->initialize();

        if ($versionNumber === null) {
            return $this->migrations;
        }
        if (!isset($this->migrations[$versionNumber])) {
            throw new \InvalidArgumentException(sprintf('Migration "%s" was not found', $versionNumber), 1421667040);
        }
        return array($versionNumber => $this->migrations[$versionNumber]);
    }


    /**
     * @param string $packageKey the package key to return migration data for
     * @return array in the format ['<packageKey' => ['packageKey' => '<packageKey>', 'category' => <Application/Framework/...>, 'path' => '<packagePath>', 'meta' => '<packageMetadata>', 'composerManifest' => '<composerData>'], [...]]
     * @throws \InvalidArgumentException
     */
    protected function getPackagesData($packageKey)
    {
        $this->initialize();

        if (!isset($this->packagesData[$packageKey])) {
            throw new \InvalidArgumentException(sprintf('Package "%s" was not found', $packageKey), 1421667044);
        }
        return array($packageKey => $this->packagesData[$packageKey]);
    }
}

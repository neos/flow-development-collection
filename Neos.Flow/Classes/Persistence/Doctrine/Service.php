<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\MigrationClassNotFound;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use Doctrine\Migrations\Finder\MigrationFinder as MigrationFinderInterface;
use Doctrine\Migrations\Generator\Exception\NoChangesDetected;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigration;
use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Tools\Console\ConsoleLogger;
use Doctrine\Migrations\Tools\Console\Exception\InvalidOptionUsage;
use Doctrine\Migrations\Tools\Console\Exception\VersionAlreadyExists;
use Doctrine\Migrations\Tools\Console\Exception\VersionDoesNotExist;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\ORM\Tools\ToolsException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Utility\Environment;
use Neos\Flow\Utility\Exception;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Neos\Utility\ObjectAccess;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Service class for tasks related to Doctrine
 *
 * @Flow\Scope("singleton")
 */
class Service
{
    public const DOCTRINE_MIGRATIONSTABLENAME = 'flow_doctrine_migrationstatus';

    public const DOCTRINE_MIGRATIONSNAMESPACE = 'Neos\Flow\Persistence\Doctrine\Migrations';

    /**
     * @Flow\Inject(lazy = false)
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @var BufferedOutput
     */
    protected $logMessages;

    /**
     * Validates the metadata mapping for Doctrine, using the SchemaValidator
     * of Doctrine.
     *
     * @return array
     */
    public function validateMapping(): array
    {
        try {
            $validator = new SchemaValidator($this->entityManager);
            return $validator->validateMapping();
        } catch (\Exception $exception) {
            return [[$exception->getMessage()]];
        }
    }

    /**
     * Creates the needed DB schema using Doctrine's SchemaTool. If tables already
     * exist, this will throw an exception.
     *
     * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
     * @return void
     * @throws ToolsException
     */
    public function createSchema($outputPathAndFilename = null): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $allMetaData = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if ($outputPathAndFilename === null) {
            $schemaTool->createSchema($allMetaData);
        } else {
            $createSchemaSqlStatements = $schemaTool->getCreateSchemaSql($allMetaData);
            file_put_contents($outputPathAndFilename, implode(PHP_EOL, $createSchemaSqlStatements));
        }
    }

    /**
     * Updates the DB schema using Doctrine's SchemaTool. The $safeMode flag is passed
     * to SchemaTool unchanged.
     *
     * @param boolean $safeMode
     * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
     * @return void
     */
    public function updateSchema($safeMode = true, $outputPathAndFilename = null): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $allMetaData = $this->entityManager->getMetadataFactory()->getAllMetadata();
        if ($outputPathAndFilename === null) {
            $schemaTool->updateSchema($allMetaData, $safeMode);
        } else {
            $updateSchemaSqlStatements = $schemaTool->getUpdateSchemaSql($allMetaData, $safeMode);
            file_put_contents($outputPathAndFilename, implode(PHP_EOL, $updateSchemaSqlStatements));
        }
    }

    /**
     * Compiles the Doctrine proxy class code using the Doctrine ProxyFactory.
     *
     * @return void
     * @throws FilesException
     * @throws Exception
     */
    public function compileProxies(): void
    {
        Files::emptyDirectoryRecursively(Files::concatenatePaths([$this->environment->getPathToTemporaryDirectory(), 'Doctrine/Proxies']));
        $proxyFactory = $this->entityManager->getProxyFactory();
        $proxyFactory->generateProxyClasses($this->entityManager->getMetadataFactory()->getAllMetadata());
    }

    /**
     * Returns information about which entities exist and possibly if their
     * mapping information contains errors or not.
     *
     * @return array
     * @throws ORMException
     */
    public function getEntityStatus(): array
    {
        if ($this->entityManager->getConfiguration()->getMetadataDriverImpl() === null) {
            throw new \RuntimeException('No metadata driver implementation configured', 1604919550);
        }

        $info = [];
        $entityClassNames = $this->entityManager->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
        foreach ($entityClassNames as $entityClassName) {
            try {
                $info[$entityClassName] = $this->entityManager->getClassMetadata($entityClassName);
            } catch (MappingException $e) {
                $info[$entityClassName] = $e->getMessage();
            }
        }

        return $info;
    }

    /**
     * Run DQL and return the result as-is.
     *
     * @param string $dql
     * @param integer $hydrationMode
     * @param int|null $firstResult
     * @param int|null $maxResult
     * @return mixed
     */
    public function runDql(string $dql, int $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT, int $firstResult = null, int $maxResult = null)
    {
        $query = $this->entityManager->createQuery($dql);
        if ($firstResult !== null) {
            $query->setFirstResult($firstResult);
        }
        if ($maxResult !== null) {
            $query->setMaxResults($maxResult);
        }

        return $query->execute([], $hydrationMode);
    }

    /**
     * Return the configuration needed for Migrations.
     *
     * @return DependencyFactory
     * @throws DBALException|FilesException
     */
    protected function getDependencyFactory(): DependencyFactory
    {
        $migrationsPath = Files::concatenatePaths([FLOW_PATH_DATA, 'DoctrineMigrations']);
        if (!is_dir($migrationsPath)) {
            Files::createDirectoryRecursively($migrationsPath);
        }
        $configurationLoader = new ConfigurationArray([
            'table_storage' => [
                'table_name' => self::DOCTRINE_MIGRATIONSTABLENAME,
                'version_column_length' => 255,
            ],
            'migrations_paths' => [
                self::DOCTRINE_MIGRATIONSNAMESPACE => $migrationsPath
            ],
        ]);
        $entityManagerLoader = new ExistingEntityManager($this->entityManager);
        $this->logMessages = new BufferedOutput(null, true);
        $logger = new ConsoleLogger($this->logMessages);

        $dependencyFactory = DependencyFactory::fromEntityManager($configurationLoader, $entityManagerLoader);
        $dependencyFactory->setService(MigrationFinderInterface::class, new MigrationFinder($this->getDatabasePlatformName()));
        $dependencyFactory->setService(LoggerInterface::class, $logger);

        return $dependencyFactory;
    }

    /**
     * Returns a formatted string of current database migration status.
     *
     * @param boolean $showMigrations
     * @return string
     * @throws DBALException
     */
    public function getFormattedMigrationStatus($showMigrations = false): string
    {
        $this->initializeMetadataStorage();

        $infosHelper = $this->getDependencyFactory()->getMigrationStatusInfosHelper();
        $infosHelper->showMigrationsInfo($this->logMessages);

        if ($showMigrations) {
            $versions = $this->getSortedVersions(
                $this->getDependencyFactory()->getMigrationPlanCalculator()->getMigrations(), // available migrations
                $this->getDependencyFactory()->getMetadataStorage()->getExecutedMigrations() // executed migrations
            );

            $this->logMessages->writeln('');
            $this->getDependencyFactory()->getMigrationStatusInfosHelper()->listVersions($versions, $this->logMessages);
        }

        return $this->logMessages->fetch();
    }

    /**
     * @param AvailableMigrationsList $availableMigrations
     * @param ExecutedMigrationsList $executedMigrations
     * @return Version[]
     * @throws DBALException
     */
    private function getSortedVersions(AvailableMigrationsList $availableMigrations, ExecutedMigrationsList $executedMigrations): array
    {
        $availableVersions = array_map(static function (AvailableMigration $availableMigration): Version {
            return $availableMigration->getVersion();
        }, $availableMigrations->getItems());

        $executedVersions = array_map(static function (ExecutedMigration $executedMigration): Version {
            return $executedMigration->getVersion();
        }, $executedMigrations->getItems());

        $versions = array_unique(array_merge($availableVersions, $executedVersions));

        $comparator = $this->getDependencyFactory()->getVersionComparator();
        uasort($versions, static function (Version $a, Version $b) use ($comparator): int {
            return $comparator->compare($a, $b);
        });

        return $versions;
    }

    /**
     * Execute all new migrations, up to $version if given.
     *
     * If $outputPathAndFilename is given, the SQL statements will be written to the given file instead of executed.
     *
     * @param string $version The version to migrate to
     * @param string|null $outputPathAndFilename A file to write SQL to, instead of executing it - implicitly enables dry-run
     * @param boolean $dryRun Whether to do a dry run or not
     * @param boolean $quiet Whether to do a quiet run or not
     * @return string
     * @throws DBALException
     */
    public function executeMigrations(string $version = 'latest', string $outputPathAndFilename = null, $dryRun = false, $quiet = false): string
    {
        $this->initializeMetadataStorage();

        $migrationRepository = $this->getDependencyFactory()->getMigrationRepository();
        if (count($migrationRepository->getMigrations()) === 0) {
            return sprintf(
                'The version "%s" can\'t be reached, there are no registered migrations.',
                $version
            );
        }

        try {
            $resolvedVersion = $this->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias($version);
        } catch (UnknownMigrationVersion $e) {
            return sprintf(
                'Unknown version: %s',
                OutputFormatter::escape($version)
            );
        } catch (NoMigrationsToExecute | NoMigrationsFoundWithCriteria $e) {
            return ($quiet === false ? $this->exitMessageForAlias($version) : '');
        }

        $planCalculator = $this->getDependencyFactory()->getMigrationPlanCalculator();
        $plan = $planCalculator->getPlanUntilVersion($resolvedVersion);
        if (count($plan) === 0) {
            return ($quiet === false ? $this->exitMessageForAlias($version) : '');
        }

        if ($quiet === false) {
            $output = sprintf(
                'Migrating%s %s to %s',
                $dryRun ? ' (dry-run)' : '',
                $plan->getDirection(),
                (string)$resolvedVersion
            );
        } else {
            $output = '';
        }

        $migratorConfiguration = new MigratorConfiguration();
        $migratorConfiguration->setDryRun($dryRun || $outputPathAndFilename !== null);

        $migrator = $this->getDependencyFactory()->getMigrator();
        $sql = $migrator->migrate($plan, $migratorConfiguration);

        if ($quiet === false) {
            $output .= PHP_EOL;
            foreach ($sql as $item) {
                $output .= PHP_EOL;
                foreach ($item as $inner) {
                    $output .= '     -> ' . $inner->getStatement() . PHP_EOL;
                }
            }
            $output .= PHP_EOL;
            $output .= $this->logMessages->fetch();
        }

        if (is_string($outputPathAndFilename)) {
            $writer = $this->getDependencyFactory()->getQueryWriter();
            $writer->write($outputPathAndFilename, $plan->getDirection(), $sql);
            if ($quiet === false) {
                $output .= PHP_EOL . sprintf('SQL written to %s', $outputPathAndFilename);
            }
        }

        return $output;
    }

    private function exitMessageForAlias(string $versionAlias): string
    {
        $version = $this->getDependencyFactory()->getVersionAliasResolver()->resolveVersionAlias('current');

        // Allow meaningful message when latest version already reached.
        if (in_array($versionAlias, ['current', 'latest', 'first'], true)) {
            $message = sprintf(
                'Already at the %s version ("%s")',
                $versionAlias,
                (string)$version
            );
        } elseif (in_array($versionAlias, ['next', 'prev'], true) || strpos($versionAlias, 'current') === 0) {
            $message = sprintf(
                'The version "%s" couldn\'t be reached, you are at version "%s"',
                $versionAlias,
                (string)$version
            );
        } else {
            $message = sprintf(
                'You are already at version "%s"',
                (string)$version
            );
        }

        return $message;
    }

    /**
     * Execute a single migration in up or down direction. If $path is given, the
     * SQL statements will be written to the file in $path instead of executed.
     *
     * @param string $version The version to migrate to
     * @param string $direction
     * @param string|null $outputPathAndFilename A file to write SQL to, instead of executing it
     * @param boolean $dryRun Whether to do a dry run or not
     * @return string
     * @throws DBALException
     */
    public function executeMigration(string $version, string $direction = 'up', string $outputPathAndFilename = null, bool $dryRun = false): string
    {
        $this->initializeMetadataStorage();

        $migrationRepository = $this->getDependencyFactory()->getMigrationRepository();
        if (!$migrationRepository->hasMigration($version)) {
            return sprintf('Version %s is not available', $version);
        }

        $migratorConfiguration = new MigratorConfiguration();
        $migratorConfiguration->setDryRun($dryRun || $outputPathAndFilename !== null);

        $planCalculator = $this->getDependencyFactory()->getMigrationPlanCalculator();
        $plan = $planCalculator->getPlanForVersions([new Version($version)], $direction);

        $output = sprintf(
            'Migrating%s %s to %s',
            $dryRun ? ' (dry-run)' : '',
            $plan->getDirection(),
            $version
        );

        $migrator = $this->getDependencyFactory()->getMigrator();
        $sql = $migrator->migrate($plan, $migratorConfiguration);

        $output .= PHP_EOL;
        foreach ($sql as $item) {
            $output .= PHP_EOL;
            foreach ($item as $inner) {
                $output .= '     -> ' . $inner->getStatement() . PHP_EOL;
            }
        }
        $output .= PHP_EOL;
        $output .= $this->logMessages->fetch();

        if ($outputPathAndFilename !== null) {
            $writer = $this->getDependencyFactory()->getQueryWriter();
            $writer->write($outputPathAndFilename, $direction, $sql);
        }

        return $output;
    }

    /**
     * Add a migration version to the migrations table or remove it.
     *
     * This does not execute any migration code but simply records a version
     * as migrated or not.
     *
     * @param string $version The version to add or remove
     * @param boolean $markAsMigrated
     * @return void
     * @throws MigrationException
     * @throws \LogicException
     * @throws DBALException
     */
    public function markAsMigrated(string $version, bool $markAsMigrated): void
    {
        $this->initializeMetadataStorage();

        $output = new BufferedOutput();

        $executedMigrations = $this->getDependencyFactory()->getMetadataStorage()->getExecutedMigrations();
        $availableVersions = $this->getDependencyFactory()->getMigrationPlanCalculator()->getMigrations();
        if ($version === 'all') {
            if ($markAsMigrated === false) {
                foreach ($executedMigrations->getItems() as $availableMigration) {
                    $this->mark($output, $availableMigration->getVersion(), false, $executedMigrations, !$markAsMigrated);
                }
            }

            foreach ($availableVersions->getItems() as $availableMigration) {
                $this->mark($output, $availableMigration->getVersion(), true, $executedMigrations, !$markAsMigrated);
            }
        } elseif ($version !== null) {
            $this->mark($output, new Version($version), false, $executedMigrations, !$markAsMigrated);
        } else {
            throw InvalidOptionUsage::new('You must specify the version or use the --all argument.');
        }
    }

    /**
     * @param OutputInterface $output
     * @param Version $version
     * @param bool $all
     * @param ExecutedMigrationsList $executedMigrations
     * @param bool $delete
     * @throws DBALException
     */
    private function mark(OutputInterface $output, Version $version, bool $all, ExecutedMigrationsList $executedMigrations, bool $delete): void
    {
        try {
            $availableMigration = $this->getDependencyFactory()->getMigrationRepository()->getMigration($version);
        } catch (MigrationClassNotFound $e) {
            $availableMigration = null;
        }

        $storage = $this->getDependencyFactory()->getMetadataStorage();
        if ($availableMigration === null) {
            if ($delete === false) {
                throw UnknownMigrationVersion::new((string)$version);
            }

            $migrationResult = new ExecutionResult($version, Direction::DOWN);
            $storage->complete($migrationResult);
            $output->writeln(sprintf(
                "<info>%s</info> deleted from the version table.\n",
                (string)$version
            ));

            return;
        }

        $marked = false;

        if ($delete === false && $executedMigrations->hasMigration($version)) {
            if (!$all) {
                throw VersionAlreadyExists::new($version);
            }

            $marked = true;
        }

        if ($delete && !$executedMigrations->hasMigration($version)) {
            if (!$all) {
                throw VersionDoesNotExist::new($version);
            }

            $marked = true;
        }

        if ($marked === true) {
            return;
        }

        if ($delete) {
            $migrationResult = new ExecutionResult($version, Direction::DOWN);
            $storage->complete($migrationResult);

            $output->writeln(sprintf(
                "<info>%s</info> deleted from the version table.\n",
                (string)$version
            ));
        } else {
            $migrationResult = new ExecutionResult($version, Direction::UP);
            $storage->complete($migrationResult);

            $output->writeln(sprintf(
                "<info>%s</info> added to the version table.\n",
                (string)$version
            ));
        }
    }

    /**
     * Returns the current migration status as an array.
     *
     * @return array
     * @return DependencyFactory
     * @throws DBALException
     */
    public function getMigrationStatus(): array
    {
        $executedMigrations = $this->getDependencyFactory()->getMetadataStorage()->getExecutedMigrations();
        $availableMigrations = $this->getDependencyFactory()->getMigrationPlanCalculator()->getMigrations();
        $executedUnavailableMigrations = $this->getDependencyFactory()->getMigrationStatusCalculator()->getExecutedUnavailableMigrations();
        $newMigrations = $this->getDependencyFactory()->getMigrationStatusCalculator()->getNewMigrations();

        return [
            'executed' => count($executedMigrations),
            'unavailable' => count($executedUnavailableMigrations),
            'available' => count($availableMigrations),
            'new' => count($newMigrations)
        ];
    }

    /**
     * Generates a new migration file and returns the path to it.
     *
     * If $diffAgainstCurrent is true, it generates a migration file with the
     * diff between current DB structure and the found mapping metadata.
     *
     * Only include tables/sequences matching the $filterExpression regexp when
     * diffing models and existing schema.
     *
     * Otherwise an empty migration skeleton is generated.
     *
     * @param boolean $diffAgainstCurrent
     * @param string|null $filterExpression
     * @return array Path to the new file
     * @throws DBALException
     */
    public function generateMigration(bool $diffAgainstCurrent = true, string $filterExpression = null): array
    {
        $fqcn = $this->getDependencyFactory()->getClassNameGenerator()->generateClassName(self::DOCTRINE_MIGRATIONSNAMESPACE);

        if ($diffAgainstCurrent === false) {
            $migrationGenerator = $this->getDependencyFactory()->getMigrationGenerator();
            $path = $migrationGenerator->generateMigration($fqcn);

            return ['Generated new migration class!', $path];
        }

        $diffGenerator = $this->getDependencyFactory()->getDiffGenerator();
        try {
            $path = $diffGenerator->generate($fqcn, $filterExpression);
        } catch (NoChangesDetected $exception) {
            return ['No changes detected', false];
        }

        return ['Generated new migration class!', $path];
    }

    /**
     * Get name of current database platform
     *
     * @return string
     * @throws DBALException
     */
    public function getDatabasePlatformName(): string
    {
        return ucfirst($this->entityManager->getConnection()->getDatabasePlatform()->getName());
    }

    /**
     * This serves a rather strange use case: renaming columns used in FK constraints.
     *
     * For a column that is used in a FK constraint to be renamed, the FK constraint has to be
     * dropped first, then the column can be renamed and last the FK constraint needs to be
     * added back (using the new name, of course).
     *
     * This method helps with the task of handling the FK constraints during this. Given a list
     * of tables that contain columns to be renamed and a search/replace pair for the column name,
     * it will return an array with arrays with drop and add SQL statements.
     *
     * Use them like this before and after renaming the affected fields:
     *
     * // collect foreign keys pointing to "our" tables
     * $tableNames = array(...);
     * $foreignKeyHandlingSql = $this->getForeignKeyHandlingSql($schema, $tableNames, 'old_name', 'new_name');
     *
     * // drop FK constraints
     * foreach ($foreignKeyHandlingSql['drop'] as $sql) {
     *     $this->addSql($sql);
     * }
     *
     * // rename columns now
     *
     * // add back FK constraints
     * foreach ($foreignKeyHandlingSql['add'] as $sql) {
     *     $this->addSql($sql);
     * }
     *
     * @param Schema $schema
     * @param AbstractPlatform $platform
     * @param array $tableNames
     * @param string $search
     * @param string $replace
     * @return array
     */
    public static function getForeignKeyHandlingSql(Schema $schema, AbstractPlatform $platform, array $tableNames, string $search, string $replace): array
    {
        $foreignKeyHandlingSql = ['drop' => [], 'add' => []];
        $tables = $schema->getTables();
        foreach ($tables as $table) {
            $foreignKeys = $table->getForeignKeys();
            foreach ($foreignKeys as $foreignKey) {
                if (!in_array($table->getName(), $tableNames, true) && !in_array($foreignKey->getForeignTableName(), $tableNames, true)) {
                    continue;
                }

                $localColumns = $foreignKey->getLocalColumns();
                $foreignColumns = $foreignKey->getForeignColumns();
                if (in_array($search, $foreignColumns) || in_array($search, $localColumns)) {
                    if (in_array($foreignKey->getLocalTableName(), $tableNames, true)) {
                        array_walk(
                            $localColumns,
                            static function (&$value) use ($search, $replace) {
                                if ($value === $search) {
                                    $value = $replace;
                                }
                            }
                        );
                    }
                    if (in_array($foreignKey->getForeignTableName(), $tableNames, true)) {
                        array_walk(
                            $foreignColumns,
                            static function (&$value) use ($search, $replace) {
                                if ($value === $search) {
                                    $value = $replace;
                                }
                            }
                        );
                    }

                    $identifierConstructorCallback = static function ($columnName) {
                        return new Identifier($columnName);
                    };
                    $localColumns = array_map($identifierConstructorCallback, $localColumns);
                    $foreignColumns = array_map($identifierConstructorCallback, $foreignColumns);

                    $newForeignKey = clone $foreignKey;
                    ObjectAccess::setProperty($newForeignKey, '_localColumnNames', $localColumns, true);
                    ObjectAccess::setProperty($newForeignKey, '_foreignColumnNames', $foreignColumns, true);
                    $foreignKeyHandlingSql['drop'][] = $platform->getDropForeignKeySQL($foreignKey, $table);
                    $foreignKeyHandlingSql['add'][] = $platform->getCreateForeignKeySQL($newForeignKey, $table);
                }
            }
        }

        return $foreignKeyHandlingSql;
    }

    /**
     * Calls `ensureInitialized()` on the Metadata Storage and applies pending changes
     * @see MetadataStorage::ensureInitialized()
     *
     * @throws DBALException | FilesException
     */
    private function initializeMetadataStorage(): void
    {
        $this->getDependencyFactory()->getMetadataStorage()->ensureInitialized();
        $this->entityManager->flush();
    }
}

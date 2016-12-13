<?php
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

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Identifier;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\SchemaValidator;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Reflection\DocCommentParser;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Utility\Exception;
use Neos\Utility\Files;

/**
 * Service class for tasks related to Doctrine
 *
 * @Flow\Scope("singleton")
 */
class Service
{
    const DOCTRINE_MIGRATIONSTABLENAME = 'flow_doctrine_migrationstatus';

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var array
     */
    public $output = [];

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Package\PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Utility\Environment
     */
    protected $environment;

    /**
     * Validates the metadata mapping for Doctrine, using the SchemaValidator
     * of Doctrine.
     *
     * @return array
     */
    public function validateMapping()
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
     * @return string
     */
    public function createSchema($outputPathAndFilename = null)
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
     * @return string
     */
    public function updateSchema($safeMode = true, $outputPathAndFilename = null)
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
     */
    public function compileProxies()
    {
        Files::emptyDirectoryRecursively(Files::concatenatePaths([$this->environment->getPathToTemporaryDirectory(), 'Doctrine/Proxies']));
        /** @var \Doctrine\ORM\Proxy\ProxyFactory $proxyFactory */
        $proxyFactory = $this->entityManager->getProxyFactory();
        $proxyFactory->generateProxyClasses($this->entityManager->getMetadataFactory()->getAllMetadata());
    }

    /**
     * Returns information about which entities exist and possibly if their
     * mapping information contains errors or not.
     *
     * @return array
     */
    public function getEntityStatus()
    {
        $info = array();
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
     * @param integer $firstResult
     * @param integer $maxResult
     * @return mixed
     */
    public function runDql($dql, $hydrationMode = \Doctrine\ORM\Query::HYDRATE_OBJECT, $firstResult = null, $maxResult = null)
    {
        /** @var \Doctrine\ORM\Query $query */
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
     * @return Configuration
     */
    protected function getMigrationConfiguration()
    {
        $this->output = [];
        $that = $this;
        $outputWriter = new OutputWriter(
            function ($message) use ($that) {
                $that->output[] = $message;
            }
        );

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->entityManager->getConnection();
        $schemaManager = $connection->getSchemaManager();
        if ($schemaManager->tablesExist(array('flow3_doctrine_migrationstatus')) === true) {
            $schemaManager->renameTable('flow3_doctrine_migrationstatus', self::DOCTRINE_MIGRATIONSTABLENAME);
        }

        $configuration = new Configuration($connection, $outputWriter);
        $configuration->setMigrationsNamespace('Neos\Flow\Persistence\Doctrine\Migrations');
        $configuration->setMigrationsDirectory(Files::concatenatePaths([FLOW_PATH_DATA, 'DoctrineMigrations']));
        $configuration->setMigrationsTableName(self::DOCTRINE_MIGRATIONSTABLENAME);

        $configuration->createMigrationTable();

        $databasePlatformName = $this->getDatabasePlatformName();
        /** @var PackageInterface $package */
        foreach ($this->packageManager->getActivePackages() as $package) {
            $path = Files::concatenatePaths([
                $package->getPackagePath(),
                'Migrations',
                $databasePlatformName
            ]);
            if (is_dir($path)) {
                $configuration->registerMigrationsFromDirectory($path);
            }
        }

        return $configuration;
    }

    /**
     * Returns the current migration status formatted as plain text.
     *
     * @param boolean $showMigrations
     * @param boolean $showDescriptions
     * @return string
     */
    public function getMigrationStatus($showMigrations = false, $showDescriptions = false)
    {
        $configuration = $this->getMigrationConfiguration();

        $executedMigrations = $configuration->getMigratedVersions();
        $availableMigrations = $configuration->getAvailableVersions();
        $executedUnavailableMigrations = array_diff($executedMigrations, $availableMigrations);

        $numExecutedUnavailableMigrations = count($executedUnavailableMigrations);
        $numNewMigrations = count(array_diff($availableMigrations, $executedMigrations));

        $statusInformation = [
            'Name' => $configuration->getName() ? $configuration->getName() : 'Doctrine Database Migrations',
            'Database Driver' => $configuration->getConnection()->getDriver()->getName(),
            'Database Name' => $configuration->getConnection()->getDatabase(),
            'Configuration Source' => 'manually configured',
            'Version Table Name' => $configuration->getMigrationsTableName(),
            'Version Column Name' => $configuration->getMigrationsColumnName(),
            'Migrations Namespace' => $configuration->getMigrationsNamespace(),
            'Migrations Target Directory' => $configuration->getMigrationsDirectory(),
            'Previous Version' => $this->getFormattedVersionAlias('prev', $configuration),
            'Current Version' => $this->getFormattedVersionAlias('current', $configuration),
            'Next Version' => $this->getFormattedVersionAlias('next', $configuration),
            'Latest Version' => $this->getFormattedVersionAlias('latest', $configuration),
            'Executed Migrations' => count($executedMigrations),
            'Executed Unavailable Migrations' => $numExecutedUnavailableMigrations,
            'Available Migrations' => count($availableMigrations),
            'New Migrations' => $numNewMigrations,
        ];

        $output = PHP_EOL . '<info>==</info> Configuration' . PHP_EOL;

        foreach ($statusInformation as $name => $value) {
            if ($name == 'New Migrations') {
                $value = $value > 0 ? '<question>' . $value . '</question>' : 0;
            }
            if ($name == 'Executed Unavailable Migrations') {
                $value = $value > 0 ? '<error>' . $value . '</error>' : 0;
            }
            $output .= '   <comment>></comment> ' . $name . ': ' . str_repeat(' ', 35 - strlen($name)) . $value . PHP_EOL;
        }

        if ($showMigrations) {
            if ($migrations = $configuration->getMigrations()) {
                $docCommentParser = new DocCommentParser();

                $output .= PHP_EOL . ' <info>==</info> Available Migration Versions' . PHP_EOL;

                /** @var Version $version */
                foreach ($migrations as $version) {
                    $packageKey = $this->getPackageKeyFromMigrationVersion($version);
                    $croppedPackageKey = strlen($packageKey) < 30 ? $packageKey : substr($packageKey, 0, 29) . '~';
                    $packageKeyColumn = ' ' . str_pad($croppedPackageKey, 30, ' ');
                    $isMigrated = in_array($version->getVersion(), $executedMigrations);
                    $status = $isMigrated ? '<info>migrated</info>' : '<error>not migrated</error>';
                    $migrationDescription = '';
                    if ($showDescriptions) {
                        $migrationDescription = str_repeat(' ', 2) . $this->getMigrationDescription($version, $docCommentParser);
                    }
                    $formattedVersion = $configuration->getDateTime($version->getVersion());

                    $output .= '    <comment>></comment> ' . $formattedVersion .
                        ' (<comment>' . $version->getVersion() . '</comment>)' . $packageKeyColumn .
                        str_repeat(' ', 2) . $status . $migrationDescription . PHP_EOL;
                }
            }

            if (count($executedUnavailableMigrations)) {
                $output .= PHP_EOL . ' <info>==</info> Previously Executed Unavailable Migration Versions' . PHP_EOL;
                foreach ($executedUnavailableMigrations as $executedUnavailableMigration) {
                    $output .= '    <comment>></comment> ' . $configuration->getDateTime($executedUnavailableMigration) .
                        ' (<comment>' . $executedUnavailableMigration . '</comment>)' . PHP_EOL;
                }
            }
        }

        return $output;
    }

    /**
     * Tries to find out a package key which the Version belongs to. If no
     * package could be found, an empty string is returned.
     *
     * @param Version $version
     * @return string
     */
    protected function getPackageKeyFromMigrationVersion(Version $version)
    {
        $sortedAvailablePackages = $this->packageManager->getAvailablePackages();
        usort($sortedAvailablePackages, function (PackageInterface $packageOne, PackageInterface $packageTwo) {
            return strlen($packageTwo->getPackagePath()) - strlen($packageOne->getPackagePath());
        });

        $reflectedClass = new \ReflectionClass($version->getMigration());
        $classPathAndFilename = Files::getUnixStylePath($reflectedClass->getFileName());

        /** @var $package PackageInterface */
        foreach ($sortedAvailablePackages as $package) {
            $packagePath = Files::getUnixStylePath($package->getPackagePath());
            if (strpos($classPathAndFilename, $packagePath) === 0) {
                return $package->getPackageKey();
            }
        }

        return '';
    }

    /**
     * Returns a formatted version string for the alias.
     *
     * @param string $alias
     * @param Configuration $configuration
     * @return string
     */
    protected function getFormattedVersionAlias($alias, Configuration $configuration)
    {
        $version = $configuration->resolveVersionAlias($alias);

        if ($version === null) {
            if ($alias == 'next') {
                return 'Already at latest version';
            } elseif ($alias == 'prev') {
                return 'Already at first version';
            }
        }

        if ($version === '0') {
            return '<comment>0</comment>';
        }

        return $configuration->getDateTime($version) . ' (<comment>' . $version . '</comment>)';
    }

    /**
     * Returns the description of a migration.
     *
     * If available it is fetched from the getDescription() method, if that returns an empty value
     * the class docblock is used instead.
     *
     * @param Version $version
     * @param DocCommentParser $parser
     * @return string
     */
    protected function getMigrationDescription(Version $version, DocCommentParser $parser)
    {
        if ($version->getMigration()->getDescription()) {
            return $version->getMigration()->getDescription();
        } else {
            $reflectedClass = new \ReflectionClass($version->getMigration());
            $parser->parseDocComment($reflectedClass->getDocComment());
            return str_replace([chr(10), chr(13)], ' ', $parser->getDescription());
        }
    }

    /**
     * Execute all new migrations, up to $version if given.
     *
     * If $outputPathAndFilename is given, the SQL statements will be written to the given file instead of executed.
     *
     * @param string $version The version to migrate to
     * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
     * @param boolean $dryRun Whether to do a dry run or not
     * @param boolean $quiet Whether to do a quiet run or not
     * @return string
     */
    public function executeMigrations($version = null, $outputPathAndFilename = null, $dryRun = false, $quiet = false)
    {
        $configuration = $this->getMigrationConfiguration();
        $migration = new Migration($configuration);

        if ($outputPathAndFilename !== null) {
            $migration->writeSqlFile($outputPathAndFilename, $version);
        } else {
            $migration->migrate($version, $dryRun);
        }

        if ($quiet === true) {
            $output = '';
            foreach ($this->output as $line) {
                $line = strip_tags($line);
                if (strpos($line, '  ++ migrating ') !== false || strpos($line, '  -- reverting ') !== false) {
                    $output .= substr($line, -15);
                }
            }
            return $output;
        } else {
            return implode(PHP_EOL, $this->output);
        }
    }

    /**
     * Execute a single migration in up or down direction. If $path is given, the
     * SQL statements will be written to the file in $path instead of executed.
     *
     * @param string $version The version to migrate to
     * @param string $direction
     * @param string $outputPathAndFilename A file to write SQL to, instead of executing it
     * @param boolean $dryRun Whether to do a dry run or not
     * @return string
     */
    public function executeMigration($version, $direction = 'up', $outputPathAndFilename = null, $dryRun = false)
    {
        $version = $this->getMigrationConfiguration()->getVersion($version);

        if ($outputPathAndFilename !== null) {
            $version->writeSqlFile($outputPathAndFilename, $direction);
        } else {
            $version->execute($direction, $dryRun);
        }
        return strip_tags(implode(PHP_EOL, $this->output));
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
     */
    public function markAsMigrated($version, $markAsMigrated)
    {
        $configuration = $this->getMigrationConfiguration();

        if ($version === 'all') {
            foreach ($configuration->getMigrations() as $version) {
                if ($markAsMigrated === true && $configuration->hasVersionMigrated($version) === false) {
                    $version->markMigrated();
                } elseif ($markAsMigrated === false && $configuration->hasVersionMigrated($version) === true) {
                    $version->markNotMigrated();
                }
            }
        } else {
            if ($configuration->hasVersion($version) === false) {
                throw MigrationException::unknownMigrationVersion($version);
            }

            $version = $configuration->getVersion($version);

            if ($markAsMigrated === true) {
                if ($configuration->hasVersionMigrated($version) === true) {
                    throw new MigrationException(sprintf('The version "%s" is already marked as executed.', $version));
                }
                $version->markMigrated();
            } else {
                if ($configuration->hasVersionMigrated($version) === false) {
                    throw new MigrationException(sprintf('The version "%s" is already marked as not executed.', $version));
                }
                $version->markNotMigrated();
            }
        }
    }

    /**
     * Generates a new migration file and returns the path to it.
     *
     * If $diffAgainstCurrent is TRUE, it generates a migration file with the
     * diff between current DB structure and the found mapping metadata.
     *
     * Only include tables/sequences matching the $filterExpression regexp when
     * diffing models and existing schema.
     *
     * Otherwise an empty migration skeleton is generated.
     *
     * @param boolean $diffAgainstCurrent
     * @param string $filterExpression
     * @return string Path to the new file
     */
    public function generateMigration($diffAgainstCurrent = true, $filterExpression = null)
    {
        $configuration = $this->getMigrationConfiguration();
        $up = null;
        $down = null;

        if ($diffAgainstCurrent === true) {
            /** @var \Doctrine\DBAL\Connection $connection */
            $connection = $this->entityManager->getConnection();

            if ($filterExpression) {
                $connection->getConfiguration()->setFilterSchemaAssetsExpression($filterExpression);
            }

            $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

            if (empty($metadata)) {
                return ['No mapping information to process.', null];
            }

            $tool = new SchemaTool($this->entityManager);

            $fromSchema = $connection->getSchemaManager()->createSchema();
            $toSchema = $tool->getSchemaFromMetadata($metadata);

            if ($filterExpression) {
                foreach ($toSchema->getTables() as $table) {
                    $tableName = $table->getName();
                    if (!preg_match($filterExpression, $this->resolveTableName($tableName))) {
                        $toSchema->dropTable($tableName);
                    }
                }

                foreach ($toSchema->getSequences() as $sequence) {
                    $sequenceName = $sequence->getName();
                    if (!preg_match($filterExpression, $this->resolveTableName($sequenceName))) {
                        $toSchema->dropSequence($sequenceName);
                    }
                }
            }

            $platform = $connection->getDatabasePlatform();
            $up = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateToSql($toSchema, $platform));
            $down = $this->buildCodeFromSql($configuration, $fromSchema->getMigrateFromSql($toSchema, $platform));

            if (!$up && !$down) {
                return ['No changes detected in your mapping information.', null];
            }
        }

        return ['Generated new migration class!', $this->writeMigrationClassToFile($configuration, $up, $down)];
    }

    /**
     * Resolve a table name from its fully qualified name. The `$name` argument
     * comes from Doctrine\DBAL\Schema\Table#getName which can sometimes return
     * a namespaced name with the form `{namespace}.{tableName}`. This extracts
     * the table name from that.
     *
     * @param string $name
     * @return string
     */
    private function resolveTableName($name)
    {
        $pos = strpos($name, '.');

        return false === $pos ? $name : substr($name, $pos + 1);
    }

    /**
     * @param Configuration $configuration
     * @param string $up
     * @param string $down
     * @return string
     * @throws \RuntimeException
     */
    protected function writeMigrationClassToFile(Configuration $configuration, $up, $down)
    {
        $namespace = $configuration->getMigrationsNamespace();
        $className = 'Version' . date('YmdHis');
        $up = $up === null ? '' : "\n        " . implode("\n        ", explode("\n", $up));
        $down = $down === null ? '' : "\n        " . implode("\n        ", explode("\n", $down));

        $path = Files::concatenatePaths([$configuration->getMigrationsDirectory(), $className . '.php']);
        try {
            Files::createDirectoryRecursively(dirname($path));
        } catch (Exception $exception) {
            throw new \RuntimeException(sprintf('Migration target directory "%s" does not exist.', dirname($path)), 1303298536, $exception);
        }

        $code = <<<EOT
<?php
namespace $namespace;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs! This block will be used as the migration description if getDescription() is not used.
 */
class $className extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * @param Schema \$schema
     * @return void
     */
    public function up(Schema \$schema)
    {
        // this up() migration is autogenerated, please modify it to your needs$up
    }

    /**
     * @param Schema \$schema
     * @return void
     */
    public function down(Schema \$schema)
    {
        // this down() migration is autogenerated, please modify it to your needs$down
    }
}
EOT;
        file_put_contents($path, $code);

        return $path;
    }

    /**
     * Returns PHP code for a migration file that "executes" the given
     * array of SQL statements.
     *
     * @param Configuration $configuration
     * @param array $sql
     * @return string
     */
    protected function buildCodeFromSql(Configuration $configuration, array $sql)
    {
        $currentPlatform = $configuration->getConnection()->getDatabasePlatform()->getName();
        $code = [];
        foreach ($sql as $query) {
            if (stripos($query, $configuration->getMigrationsTableName()) !== false) {
                continue;
            }
            $code[] = sprintf('$this->addSql(%s);', var_export($query, true));
        }

        if (!empty($code)) {
            array_unshift(
                $code,
                sprintf(
                    '$this->abortIf($this->connection->getDatabasePlatform()->getName() != %s, %s);',
                    var_export($currentPlatform, true),
                    var_export(sprintf('Migration can only be executed safely on "%s".', $currentPlatform), true)
                ),
                ''
            );
        }

        return implode(chr(10), $code);
    }

    /**
     * Get name of current database platform
     *
     * @return string
     */
    public function getDatabasePlatformName()
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
    public static function getForeignKeyHandlingSql(Schema $schema, AbstractPlatform $platform, $tableNames, $search, $replace)
    {
        $foreignKeyHandlingSql = ['drop' => [], 'add' => []];
        $tables = $schema->getTables();
        foreach ($tables as $table) {
            $foreignKeys = $table->getForeignKeys();
            foreach ($foreignKeys as $foreignKey) {
                if (!in_array($table->getName(), $tableNames) && !in_array($foreignKey->getForeignTableName(), $tableNames)) {
                    continue;
                }

                $localColumns = $foreignKey->getLocalColumns();
                $foreignColumns = $foreignKey->getForeignColumns();
                if (in_array($search, $foreignColumns) || in_array($search, $localColumns)) {
                    if (in_array($foreignKey->getLocalTableName(), $tableNames)) {
                        array_walk(
                            $localColumns,
                            function (&$value) use ($search, $replace) {
                                if ($value === $search) {
                                    $value = $replace;
                                }
                            }
                        );
                    }
                    if (in_array($foreignKey->getForeignTableName(), $tableNames)) {
                        array_walk(
                            $foreignColumns,
                            function (&$value) use ($search, $replace) {
                                if ($value === $search) {
                                    $value = $replace;
                                }
                            }
                        );
                    }

                    $identifierConstructorCallback = function ($columnName) {
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
}

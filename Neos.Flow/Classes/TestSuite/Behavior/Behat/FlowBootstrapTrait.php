<?php
declare(strict_types=1);

namespace Neos\Flow\TestSuite\Behaviour\Behat;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Neos\Flow\Cli\CommandRequestHandler;
use Neos\Flow\Core\Booting\Scripts;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\Doctrine\Service;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Policy\PolicyService;

trait FlowBootstrapTrait
{
    /**
     * @var Bootstrap
     */
    static protected $bootstrap;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Doctrine\DBAL\Schema\Schema
     */
    protected static $databaseSchema;

    /**
     * Create a flow bootstrap instance
     */
    protected function initializeFlow(): Bootstrap
    {
        require_once(__DIR__ . '/../../../Core/Bootstrap.php');
        if (!defined('FLOW_PATH_ROOT')) {
            define('FLOW_PATH_ROOT', realpath(__DIR__ . '/../../../../..') . '/');
        }
        // The new classloader needs warnings converted to exceptions
        if (!defined('BEHAT_ERROR_REPORTING')) {
            define('BEHAT_ERROR_REPORTING', E_ALL);
        }
        $bootstrap = new Bootstrap('Testing/Behat');
        Scripts::initializeClassLoader($bootstrap);
        Scripts::initializeSignalSlot($bootstrap);
        Scripts::initializePackageManagement($bootstrap);
        // FIXME: We NEED to define a request due to return type declarations, and with the
        // current state of the behat test (setup) we cannot use a Http\RequestHandler because
        // some code would then try to access the httpRequest and Response which is not available,
        // so we need to think if we "mock" the whole component chain and a Http\RequestHandler or
        // live with having a CommandRequestHandler here. (A specialisted TestHandler for this case
        // would probably be a good idea.
        $bootstrap->setActiveRequestHandler(new CommandRequestHandler($bootstrap));
        $bootstrap->buildRuntimeSequence()->invoke($bootstrap);

        return $bootstrap;
    }

    public function getObjectManager(): ObjectManagerInterface
    {
        return $this->objectManager;
    }

    /**
     * @AfterSuite
     */
    public static function shutdownFlow(): void
    {
        if (self::$bootstrap !== null) {
            self::$bootstrap->shutdown('Runtime');
        }
    }

    /**
     * @BeforeScenario @fixtures
     */
    public function resetTestFixtures(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->objectManager->get(EntityManagerInterface::class);
        $entityManager->clear();

        if (self::$databaseSchema !== null) {
            $this->truncateTables($entityManager);
        } else {
            try {
                /** @var Service $doctrineService */
                $doctrineService = $this->objectManager->get(Service::class);
                $doctrineService->executeMigrations();
                $needsTruncate = true;
            } catch (DBALException $exception) {
                // Do an initial teardown to drop the schema cleanly
                $this->objectManager->get(PersistenceManagerInterface::class)->tearDown();

                /** @var Service $doctrineService */
                $doctrineService = $this->objectManager->get(Service::class);
                $doctrineService->executeMigrations();
                $needsTruncate = false;
            } catch (\PDOException $exception) {
                if ($exception->getMessage() !== 'There is no active transaction') {
                    throw $exception;
                }
                $needsTruncate = true;
            }

            $schema = $entityManager->getConnection()->getSchemaManager()->createSchema();
            self::$databaseSchema = $schema;

            if ($needsTruncate) {
                $this->truncateTables($entityManager);
            }

            // FIXME Check if this is needed at all!
            $proxyFactory = $entityManager->getProxyFactory();
            $proxyFactory->generateProxyClasses($entityManager->getMetadataFactory()->getAllMetadata());
        }

        $this->resetPolicyService();
    }

    /**
     * Truncate all known tables
     *
     * @param EntityManagerInterface $entityManager
     * @return void
     */
    private function truncateTables(EntityManagerInterface $entityManager): void
    {
        $connection = $entityManager->getConnection();

        $tables = array_filter(self::$databaseSchema->getTables(), function ($table) {
            return $table->getName() !== 'flow_doctrine_migrationstatus';
        });
        switch ($connection->getDatabasePlatform()->getName()) {
            case 'mysql':
                $sql = 'SET FOREIGN_KEY_CHECKS=0;';
                foreach ($tables as $table) {
                    $sql .= 'TRUNCATE `' . $table->getName() . '`;';
                }
                $sql .= 'SET FOREIGN_KEY_CHECKS=1;';
                $connection->executeQuery($sql);
                break;
            case 'sqlite':
                $sql = 'PRAGMA foreign_keys = OFF;';
                foreach ($tables as $table) {
                    $sql .= 'DELETE FROM `' . $table->getName() . '`;';
                }
                $sql .= 'PRAGMA foreign_keys = ON;';
                $connection->executeQuery($sql);
                break;
            case 'postgresql':
            default:
                foreach ($tables as $table) {
                    $sql = 'TRUNCATE ' . $table->getName() . ' CASCADE;';
                    $connection->executeQuery($sql);
                }
                break;
        }
    }

    /**
     * Reset policy service
     *
     * This is needed to remove cached role entities after resetting the database.
     *
     * @return void
     */
    private function resetPolicyService(): void
    {
        $this->objectManager->get(PolicyService::class)->reset();
    }
}

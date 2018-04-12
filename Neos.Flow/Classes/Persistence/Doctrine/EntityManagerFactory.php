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

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Files;

/**
 * EntityManager factory for Doctrine integration
 *
 * @Flow\Scope("singleton")
 */
class EntityManagerFactory
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var Environment
     */
    protected $environment;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * Injects the Flow settings, the persistence part is kept
     * for further use.
     *
     * @param array $settings
     * @return void
     * @throws InvalidConfigurationException
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings['persistence'];
        if (!is_array($this->settings['backendOptions'])) {
            throw new InvalidConfigurationException(sprintf('The Neos.Flow.persistence.backendOptions settings need to be an array, %s given.', gettype($this->settings['backendOptions'])), 1426149224);
        }
        if (!is_array($this->settings['doctrine']['secondLevelCache'])) {
            throw new InvalidConfigurationException(sprintf('The TYPO3.Flow.persistence.doctrine.secondLevelCache settings need to be an array, %s given.', gettype($this->settings['doctrine']['secondLevelCache'])), 1491305513);
        }
    }

    /**
     * Factory method which creates an EntityManager.
     *
     * @return EntityManager
     * @throws InvalidConfigurationException
     */
    public function create()
    {
        $config = new Configuration();
        $config->setClassMetadataFactoryName(Mapping\ClassMetadataFactory::class);

        $eventManager = new EventManager();

        $flowAnnotationDriver = $this->objectManager->get(FlowAnnotationDriver::class);
        $config->setMetadataDriverImpl($flowAnnotationDriver);

        $proxyDirectory = Files::concatenatePaths([$this->environment->getPathToTemporaryDirectory(), 'Doctrine/Proxies']);
        Files::createDirectoryRecursively($proxyDirectory);
        $config->setProxyDir($proxyDirectory);
        $config->setProxyNamespace('Neos\Flow\Persistence\Doctrine\Proxies');
        $config->setAutoGenerateProxyClasses(false);

        // Set default host to 127.0.0.1 if there is no host configured but a dbname
        if (empty($this->settings['backendOptions']['host']) && !empty($this->settings['backendOptions']['dbname']) && empty($this->settings['backendOptions']['unix_socket'])) {
            $this->settings['backendOptions']['host'] = '127.0.0.1';
        }

        // The following code tries to connect first, if that succeeds, all is well. If not, the platform is fetched directly from the
        // driver - without version checks to the database server (to which no connection can be made) - and is added to the config
        // which is then used to create a new connection. This connection will then return the platform directly, without trying to
        // detect the version it runs on, which fails if no connection can be made. But the platform is used even if no connection can
        // be made, which was no problem with Doctrine DBAL 2.3. And then came version-aware drivers and platforms...
        $connection = DriverManager::getConnection($this->settings['backendOptions'], $config, $eventManager);
        try {
            $connection->connect();
        } catch (ConnectionException $exception) {
            $settings = $this->settings['backendOptions'];
            $settings['platform'] = $connection->getDriver()->getDatabasePlatform();
            $connection = DriverManager::getConnection($settings, $config, $eventManager);
        }

        $this->emitBeforeDoctrineEntityManagerCreation($connection, $config, $eventManager);
        $entityManager = EntityManager::create($connection, $config, $eventManager);
        $flowAnnotationDriver->setEntityManager($entityManager);
        $this->emitAfterDoctrineEntityManagerCreation($config, $entityManager);

        return $entityManager;
    }

    /**
     * @param Connection $connection
     * @param Configuration $config
     * @param EventManager $eventManager
     * @Flow\Signal
     */
    public function emitBeforeDoctrineEntityManagerCreation(Connection $connection, Configuration $config, EventManager $eventManager)
    {
    }

    /**
     * @param Configuration $config
     * @param EntityManager $entityManager
     * @Flow\Signal
     */
    public function emitAfterDoctrineEntityManagerCreation(Configuration $config, EntityManager $entityManager)
    {
    }
}

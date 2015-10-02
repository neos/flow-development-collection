<?php
namespace TYPO3\Flow\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Doctrine\ORM\Configuration;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\Exception\InvalidConfigurationException;
use TYPO3\Flow\Persistence\Doctrine\Mapping\Driver\FlowAnnotationDriver;
use TYPO3\Flow\Utility\Files;

/**
 * EntityManager factory for Doctrine integration
 *
 * @Flow\Scope("singleton")
 */
class EntityManagerFactory extends BaseEntityManagerFactory
{
    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Utility\Environment
     */
    protected $environment;

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
        if (!isset($settings['persistence'])) {
            throw new InvalidConfigurationException('The configuration TYPO3.Flow.persistence is NULL, please check your settings.', 1392800005);
        }
        $this->settings = $settings['persistence'];
    }

    /**
     * Factory method which creates an EntityManager.
     *
     * @param array $settings

     * @return \Doctrine\ORM\EntityManager
     * @see BaseEntityManagerFactory::create
     */
    public function create(array $settings = [])
    {
        $settings = array_merge($this->settings, $settings);
        return parent::create($settings);
    }

    /**
     * Get the annotation driver responsible for this connection.
     *
     * @param array $settings
     * @return FlowAnnotationDriver
     */
    protected function getAnnotationDriver(array $settings = [])
    {
        return $this->objectManager->get(FlowAnnotationDriver::class);
    }

    /**
     * @param Configuration $doctrineConfiguration
     * @param array $settings
     * @return void
     */
    protected function setProxyPath(Configuration $doctrineConfiguration, $settings)
    {
        if (!isset($settings['doctrine']['proxyPath'])) {
            $settings['doctrine']['proxyPath'] = Files::concatenatePaths([$this->environment->getPathToTemporaryDirectory(), 'Doctrine/Proxies']);
        }

        parent::setProxyPath($doctrineConfiguration, $settings);
    }
}

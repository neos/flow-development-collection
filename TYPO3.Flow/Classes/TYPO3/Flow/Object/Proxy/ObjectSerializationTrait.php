<?php
namespace TYPO3\Flow\Object\Proxy;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Methods used to serialize objects used by proxy classes.
 *
 */
trait ObjectSerializationTrait
{
    /**
     * Code to find and serialize entities on sleep
     *
     * @param array $transientProperties
     * @param array $propertyVarTags
     * @return array
     */
    private function Flow_serializeRelatedEntities(array $transientProperties, array $propertyVarTags)
    {
        $reflectedClass = new \ReflectionClass(__CLASS__);
        $allReflectedProperties = $reflectedClass->getProperties();
        foreach ($allReflectedProperties as $reflectionProperty) {
            $propertyName = $reflectionProperty->name;
            if (in_array($propertyName, [
                'Flow_Aop_Proxy_targetMethodsAndGroupedAdvices',
                'Flow_Aop_Proxy_groupedAdviceChains',
                'Flow_Aop_Proxy_methodIsInAdviceMode'
            ])) {
                continue;
            }
            if (isset($this->Flow_Injected_Properties) && is_array($this->Flow_Injected_Properties) && in_array($propertyName, $this->Flow_Injected_Properties)) {
                continue;
            }
            if ($reflectionProperty->isStatic() || in_array($propertyName, $transientProperties)) {
                continue;
            }
            if (is_array($this->$propertyName) || (is_object($this->$propertyName) && ($this->$propertyName instanceof \ArrayObject || $this->$propertyName instanceof \SplObjectStorage || $this->$propertyName instanceof \Doctrine\Common\Collections\Collection))) {
                if (count($this->$propertyName) > 0) {
                    foreach ($this->$propertyName as $key => $value) {
                        $this->Flow_searchForEntitiesAndStoreIdentifierArray((string)$key, $value, $propertyName);
                    }
                }
            }
            if (is_object($this->$propertyName) && !$this->$propertyName instanceof \Doctrine\Common\Collections\Collection) {
                if ($this->$propertyName instanceof \Doctrine\ORM\Proxy\Proxy) {
                    $className = get_parent_class($this->$propertyName);
                } else {
                    if (isset($propertyVarTags[$propertyName])) {
                        $className = trim($propertyVarTags[$propertyName], '\\');
                    }
                    if (\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->isRegistered($className) === false) {
                        $className = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getObjectNameByClassName(get_class($this->$propertyName));
                    }
                }
                if ($this->$propertyName instanceof \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface && !\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class)->isNewObject($this->$propertyName) || $this->$propertyName instanceof \Doctrine\ORM\Proxy\Proxy) {
                    if (!property_exists($this, 'Flow_Persistence_RelatedEntities') || !is_array($this->Flow_Persistence_RelatedEntities)) {
                        $this->Flow_Persistence_RelatedEntities = [];
                        $this->Flow_Object_PropertiesToSerialize[] = 'Flow_Persistence_RelatedEntities';
                    }
                    $identifier = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\TYPO3\Flow\Persistence\PersistenceManagerInterface::class)->getIdentifierByObject($this->$propertyName);
                    if (!$identifier && $this->$propertyName instanceof \Doctrine\ORM\Proxy\Proxy) {
                        $identifier = current(\TYPO3\Flow\Reflection\ObjectAccess::getProperty($this->$propertyName, '_identifier', true));
                    }
                    $this->Flow_Persistence_RelatedEntities[$propertyName] = [
                        'propertyName' => $propertyName,
                        'entityType' => $className,
                        'identifier' => $identifier
                    ];
                    continue;
                }
                if ($className !== false && (\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getScope($className) === \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_SINGLETON || $className === \TYPO3\Flow\Object\DependencyInjection\DependencyProxy::class)) {
                    continue;
                }
            }
            $this->Flow_Object_PropertiesToSerialize[] = $propertyName;
        }

        return $this->Flow_Object_PropertiesToSerialize;
    }

    /**
     * Serialize entities that are inside an array or SplObjectStorage
     *
     * @param string $path
     * @param mixed $propertyValue
     * @param string $originalPropertyName
     * @return void
     */
    private function Flow_searchForEntitiesAndStoreIdentifierArray($path, $propertyValue, $originalPropertyName)
    {
        if (is_array($propertyValue) || (is_object($propertyValue) && ($propertyValue instanceof \ArrayObject || $propertyValue instanceof \SplObjectStorage))) {
            foreach ($propertyValue as $key => $value) {
                $this->Flow_searchForEntitiesAndStoreIdentifierArray($path . '.' . $key, $value, $originalPropertyName);
            }
        } elseif ($propertyValue instanceof \TYPO3\Flow\Persistence\Aspect\PersistenceMagicInterface && !\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->isNewObject($propertyValue) || $propertyValue instanceof \Doctrine\ORM\Proxy\Proxy) {
            if (!property_exists($this, 'Flow_Persistence_RelatedEntities') || !is_array($this->Flow_Persistence_RelatedEntities)) {
                $this->Flow_Persistence_RelatedEntities = [];
                $this->Flow_Object_PropertiesToSerialize[] = 'Flow_Persistence_RelatedEntities';
            }
            if ($propertyValue instanceof \Doctrine\ORM\Proxy\Proxy) {
                $className = get_parent_class($propertyValue);
            } else {
                $className = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getObjectNameByClassName(get_class($propertyValue));
            }
            $identifier = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface')->getIdentifierByObject($propertyValue);
            if (!$identifier && $propertyValue instanceof \Doctrine\ORM\Proxy\Proxy) {
                $identifier = current(\TYPO3\Flow\Reflection\ObjectAccess::getProperty($propertyValue, '_identifier', true));
            }
            $this->Flow_Persistence_RelatedEntities[$originalPropertyName . '.' . $path] = [
                'propertyName' => $originalPropertyName,
                'entityType' => $className,
                'identifier' => $identifier,
                'entityPath' => $path
            ];
            $this->$originalPropertyName = \TYPO3\Flow\Utility\Arrays::setValueByPath($this->$originalPropertyName, $path, null);
        }
    }

    /**
     * Reconstitues related entities to an unserialized object in __wakeup.
     * Used in __wakeup methods of proxy classes.
     *
     * Note: This method adds code which ignores objects of type TYPO3\Flow\Resource\ResourcePointer in order to provide
     * backwards compatibility data generated with Flow 2.2.x which still provided that class.
     *
     * @return void
     */
    private function Flow_setRelatedEntities()
    {
        if (property_exists($this, 'Flow_Persistence_RelatedEntities') && is_array($this->Flow_Persistence_RelatedEntities)) {
            $persistenceManager = \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get('TYPO3\Flow\Persistence\PersistenceManagerInterface');
            foreach ($this->Flow_Persistence_RelatedEntities as $entityInformation) {
                if ($entityInformation['entityType'] === 'TYPO3\Flow\Resource\ResourcePointer') {
                    continue;
                }
                $entity = $persistenceManager->getObjectByIdentifier($entityInformation['identifier'], $entityInformation['entityType'], true);
                if (isset($entityInformation['entityPath'])) {
                    $this->{$entityInformation['propertyName']} = \TYPO3\Flow\Utility\Arrays::setValueByPath($this->{$entityInformation['propertyName']}, $entityInformation['entityPath'], $entity);
                } else {
                    $this->{$entityInformation['propertyName']} = $entity;
                }
            }
            unset($this->Flow_Persistence_RelatedEntities);
        }
    }
}

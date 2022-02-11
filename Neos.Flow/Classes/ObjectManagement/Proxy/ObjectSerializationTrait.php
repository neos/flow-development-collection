<?php
namespace Neos\Flow\ObjectManagement\Proxy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Proxy\Proxy as OrmProxy;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\Persistence\Aspect\PersistenceMagicInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\Arrays;

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
            if (is_array($this->$propertyName) || (is_object($this->$propertyName) && ($this->$propertyName instanceof \ArrayObject || $this->$propertyName instanceof \SplObjectStorage || $this->$propertyName instanceof Collection))) {
                if (count($this->$propertyName) > 0) {
                    foreach ($this->$propertyName as $key => $value) {
                        $this->Flow_searchForEntitiesAndStoreIdentifierArray((string)$key, $value, $propertyName);
                    }
                }
            }
            if (is_object($this->$propertyName) && !$this->$propertyName instanceof Collection) {
                if ($this->$propertyName instanceof OrmProxy) {
                    $className = get_parent_class($this->$propertyName);
                } else {
                    if (isset($propertyVarTags[$propertyName])) {
                        $className = trim($propertyVarTags[$propertyName], '\\');
                    }
                    if (Bootstrap::$staticObjectManager->isRegistered($className) === false) {
                        $className = Bootstrap::$staticObjectManager->getObjectNameByClassName(get_class($this->$propertyName));
                    }
                }
                if ($this->$propertyName instanceof PersistenceMagicInterface && !Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class)->isNewObject($this->$propertyName) || $this->$propertyName instanceof OrmProxy) {
                    if (!property_exists($this, 'Flow_Persistence_RelatedEntities') || !is_array($this->Flow_Persistence_RelatedEntities)) {
                        $this->Flow_Persistence_RelatedEntities = [];
                        $this->Flow_Object_PropertiesToSerialize[] = 'Flow_Persistence_RelatedEntities';
                    }
                    $identifier = Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class)->getIdentifierByObject($this->$propertyName);
                    if (!$identifier && $this->$propertyName instanceof OrmProxy) {
                        $identifier = current(ObjectAccess::getProperty($this->$propertyName, '_identifier', true));
                    }
                    $this->Flow_Persistence_RelatedEntities[$propertyName] = [
                        'propertyName' => $propertyName,
                        'entityType' => $className,
                        'identifier' => $identifier
                    ];
                    continue;
                }
                if ($className !== false && (Bootstrap::$staticObjectManager->getScope($className) === Configuration::SCOPE_SINGLETON || $className === DependencyProxy::class)) {
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
        } elseif ($propertyValue instanceof PersistenceMagicInterface && !Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class)->isNewObject($propertyValue) || $propertyValue instanceof OrmProxy) {
            if (!property_exists($this, 'Flow_Persistence_RelatedEntities') || !is_array($this->Flow_Persistence_RelatedEntities)) {
                $this->Flow_Persistence_RelatedEntities = [];
                $this->Flow_Object_PropertiesToSerialize[] = 'Flow_Persistence_RelatedEntities';
            }
            if ($propertyValue instanceof OrmProxy) {
                $className = get_parent_class($propertyValue);
            } else {
                $className = Bootstrap::$staticObjectManager->getObjectNameByClassName(get_class($propertyValue));
            }
            $identifier = Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class)->getIdentifierByObject($propertyValue);
            if (!$identifier && $propertyValue instanceof OrmProxy) {
                $identifier = current(ObjectAccess::getProperty($propertyValue, '_identifier', true));
            }
            $this->Flow_Persistence_RelatedEntities[$originalPropertyName . '.' . $path] = [
                'propertyName' => $originalPropertyName,
                'entityType' => $className,
                'identifier' => $identifier,
                'entityPath' => $path
            ];
            $this->$originalPropertyName = Arrays::setValueByPath($this->$originalPropertyName, $path, null);
        }
    }

    /**
     * Reconstitues related entities to an unserialized object in __wakeup.
     * Used in __wakeup methods of proxy classes.
     *
     * Note: This method adds code which ignores objects of type Neos\Flow\ResourceManagement\ResourcePointer in order to provide
     * backwards compatibility data generated with Flow 2.2.x which still provided that class.
     *
     * @return void
     */
    private function Flow_setRelatedEntities()
    {
        if (property_exists($this, 'Flow_Persistence_RelatedEntities') && is_array($this->Flow_Persistence_RelatedEntities)) {
            $persistenceManager = Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class);
            foreach ($this->Flow_Persistence_RelatedEntities as $entityInformation) {
                $entity = $persistenceManager->getObjectByIdentifier($entityInformation['identifier'], $entityInformation['entityType'], true);
                if (isset($entityInformation['entityPath'])) {
                    $this->{$entityInformation['propertyName']} = Arrays::setValueByPath($this->{$entityInformation['propertyName']}, $entityInformation['entityPath'], $entity);
                } else {
                    $this->{$entityInformation['propertyName']} = $entity;
                }
            }
            unset($this->Flow_Persistence_RelatedEntities);
        }
    }
}

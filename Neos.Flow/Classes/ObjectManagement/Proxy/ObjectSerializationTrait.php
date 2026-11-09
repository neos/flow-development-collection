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
use Doctrine\Persistence\Proxy as DoctrineProxy;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\Persistence\Aspect\PersistenceMagicInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\Arrays;

/**
 * Methods used to serialize objects used by proxy classes.
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
    private function Flow_serializeRelatedEntities(array $transientProperties, array $propertyVarTags): array
    {
        $propertiesToSerialize = [];
        $reflectedClass = new \ReflectionClass(__CLASS__);
        $allReflectedProperties = $reflectedClass->getProperties();
        foreach ($allReflectedProperties as $reflectionProperty) {
            $propertyName = $reflectionProperty->name;
            if (in_array($propertyName, [
                'Flow_Aop_Proxy_targetMethodsAndGroupedAdvices',
                'Flow_Aop_Proxy_groupedAdviceChains',
                'Flow_Aop_Proxy_methodIsInAdviceMode',
                'Flow_Injected_Properties',
            ])) {
                continue;
            }
            if (property_exists($this, 'Flow_Injected_Properties') && is_array($this->Flow_Injected_Properties) && in_array($propertyName, $this->Flow_Injected_Properties, true)) {
                continue;
            }
            if ($reflectionProperty->isStatic() || in_array($propertyName, $transientProperties, true)) {
                continue;
            }
            if (is_array($this->$propertyName) || ($this->$propertyName instanceof \ArrayObject || $this->$propertyName instanceof \SplObjectStorage || $this->$propertyName instanceof Collection)) {
                if (count($this->$propertyName) > 0) {
                    foreach ($this->$propertyName as $key => $value) {
                        $this->Flow_searchForEntitiesAndStoreIdentifierArray((string)$key, $value, $propertyName);
                    }
                }
            }

            if (is_object($this->$propertyName) && !$this->$propertyName instanceof Collection) {
                if ($this->$propertyName instanceof DoctrineProxy) {
                    $className = get_parent_class($this->$propertyName);
                } else {
                    if (isset($propertyVarTags[$propertyName])) {
                        $className = trim($propertyVarTags[$propertyName], '\\');
                    } else {
                        $className = $reflectionProperty->getType()?->getName();
                    }
                    if (Bootstrap::$staticObjectManager->isRegistered($className) === false) {
                        $className = Bootstrap::$staticObjectManager->getObjectNameByClassName(get_class($this->$propertyName));
                    }
                }
                if ($this->$propertyName instanceof DoctrineProxy || ($this->$propertyName instanceof PersistenceMagicInterface && !Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class)->isNewObject($this->$propertyName))) {
                    $this->Flow_searchForEntitiesAndStoreIdentifierArray('', $this->$propertyName, $propertyName);
                    continue;
                }
                if ($className !== false &&
                    (
                        Bootstrap::$staticObjectManager->getScope($className) === Configuration::SCOPE_SINGLETON
                        || Bootstrap::$staticObjectManager->getScope($className) === Configuration::SCOPE_SESSION
                    )) {
                    continue;
                }
            }
            $propertiesToSerialize[] = $propertyName;
        }

        return $propertiesToSerialize;
    }

    /**
     * Serialize entities that are inside an array or SplObjectStorage
     *
     * @param string $path
     * @param mixed $propertyValue
     * @param string $originalPropertyName
     * @return bool if an entity was found
     */
    private function Flow_searchForEntitiesAndStoreIdentifierArray(string $path, mixed $propertyValue, string $originalPropertyName): bool
    {
        $foundEntity = false;
        if (is_array($propertyValue) || ($propertyValue instanceof \ArrayObject || $propertyValue instanceof \SplObjectStorage)) {
            foreach ($propertyValue as $key => $value) {
                $foundEntity = $foundEntity || $this->Flow_searchForEntitiesAndStoreIdentifierArray($path . '.' . $key, $value, $originalPropertyName);
            }
        } elseif ($propertyValue instanceof DoctrineProxy || ($propertyValue instanceof PersistenceMagicInterface && !Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class)->isNewObject($propertyValue))) {
            if (!isset($this->Flow_Persistence_RelatedEntitiesContainer)) {
                throw new \RuntimeException(sprintf('The class "%s" has an entity reference Flow could not detect in property "%s" and path "%s", please add a Flow\\Proxy annotation with "forceSerializationCode" set to "true".', $this::class, $originalPropertyName, $path), 1756936954);
            }
            $this->Flow_Persistence_RelatedEntitiesContainer->appendRelatedEntity($originalPropertyName, $path, $propertyValue);
            /**
             * The idea of setting to null here is to prevent serialization after we found an entity, BUT this logic
             * is heavily flawed in today's PHP world. Type hinting might make null an invalid value. Also
             * Arrays::setValueByPath() only works on "Array-like" not on objects, therefore
             * we don't handle direct properties of $this (path empty string) at all here.
             * They are skipped for serialization in Flow_serializeRelatedEntities so we don't need to unset.
             * This still leaves the option of types going awry somewhere, but at the moment there
             * isn't really a better solution at hand and the case should be super rare.
             */
            if ($path !== '') {
                $this->$originalPropertyName = Arrays::setValueByPath($this->$originalPropertyName, $path, null);
            }
            $foundEntity = true;
        }

        return $foundEntity;
    }

    /**
     * Reconstitutes related entities to a deserialized object in __wakeup.
     * Used in __wakeup methods of proxy classes.
     *
     * @return void
     */
    private function Flow_setRelatedEntities(): void
    {
        if (isset($this->Flow_Persistence_RelatedEntitiesContainer)) {
            $persistenceManager = Bootstrap::$staticObjectManager->get(PersistenceManagerInterface::class);
            foreach ($this->Flow_Persistence_RelatedEntitiesContainer as $entityInformation) {
                $entity = $persistenceManager->getObjectByIdentifier($entityInformation['i'], $entityInformation['c'], true);
                if ($entityInformation['p'] !== '') {
                    $this->{$entityInformation['n']} = Arrays::setValueByPath($this->{$entityInformation['n']}, $entityInformation['p'], $entity);
                } else {
                    $this->{$entityInformation['n']} = $entity;
                }
            }

            isset($this->Flow_Persistence_RelatedEntitiesContainer) && $this->Flow_Persistence_RelatedEntitiesContainer->reset();
        }
    }
}

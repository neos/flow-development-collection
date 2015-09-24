<?php
namespace TYPO3\Flow\Security\Policy;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMappingConfigurationInterface;
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;
use TYPO3\Flow\Security\Exception\NoSuchRoleException;
use TYPO3\Flow\Validation\Error;

/**
 * This converter transforms strings to role instances
 *
 * @Flow\Scope("singleton")
 */
class RoleConverter extends AbstractTypeConverter
{
    /**
     * @var array
     */
    protected $sourceTypes = array('string');

    /**
     * @var string
     */
    protected $targetType = \TYPO3\Flow\Security\Policy\Role::class;

    /**
     * @var integer
     */
    protected $priority = 0;

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    /**
     * Convert an object from $source to an object.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface $configuration
     * @return object the target type
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), PropertyMappingConfigurationInterface $configuration = null)
    {
        try {
            $role = $this->policyService->getRole($source);
        } catch (NoSuchRoleException $exception) {
            return new Error('Could not find a role with the identifier "%s".', 1397212327, array($source));
        }
        return $role;
    }
}

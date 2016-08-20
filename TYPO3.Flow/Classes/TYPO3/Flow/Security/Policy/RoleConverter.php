<?php
namespace TYPO3\Flow\Security\Policy;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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

<?php
namespace TYPO3\Flow\Property\TypeConverter;

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

/**
 * This converter transforms a session identifier into a real session object.
 *
 * Given a session ID this will return an instance of TYPO3\Flow\Session\Session.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class SessionConverter extends AbstractTypeConverter
{
    /**
     * @var string
     */
    const PATTERN_MATCH_SESSIONIDENTIFIER = '/([a-zA-Z0-9]){32}/';

    /**
     * @var array
     */
    protected $sourceTypes = array('string');

    /**
     * @var string
     */
    protected $targetType = 'TYPO3\Flow\Session\Session';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Session\SessionManagerInterface
     */
    protected $sessionManager;

    /**
     * This implementation always returns TRUE for this method.
     *
     * @param mixed $source the source data
     * @param string $targetType the type to convert to.
     * @return boolean TRUE if this TypeConverter can convert from $source to $targetType, FALSE otherwise.
     * @api
     */
    public function canConvertFrom($source, $targetType)
    {
        return (preg_match(self::PATTERN_MATCH_SESSIONIDENTIFIER, $source) === 1) && ($targetType === $this->targetType);
    }

    /**
     * Convert a session identifier from $source to a Session object
     *
     * @param string $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
     * @return object the target type
     * @throws \TYPO3\Flow\Property\Exception\InvalidTargetException
     * @throws \InvalidArgumentException
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        return $this->sessionManager->getSession($source);
    }
}

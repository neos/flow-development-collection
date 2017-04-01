<?php
namespace TYPO3\Flow\Tests\Functional\Command;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Property\TypeConverter\AbstractTypeConverter;

/**
 * A type converter to create behat scenario table parameters. This is
 * needed when processing behat scenarios/steps in an isolated process.
 */
class TableNodeConverter extends AbstractTypeConverter
{
    /**
     * @var array<string>
     */
    protected $sourceTypes = array('string');

    /**
     * @var string
     */
    protected $targetType = 'TYPO3\Flow\Tests\Functional\Command\TableNode';

    /**
     * @var integer
     */
    protected $priority = 1;

    /**
     * Actually convert from $source to $targetType, by doing a typecast.
     *
     * @param mixed $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration
     * @return float|\TYPO3\Flow\Error\Error
     * @api
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = array(), \TYPO3\Flow\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        return new TableNode(json_decode($source, true));
    }
}

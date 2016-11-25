<?php
namespace Neos\Flow\Annotations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\Annotation as DoctrineAnnotation;

/**
 * @Annotation
 * @DoctrineAnnotation\Target({"METHOD"})
 */
final class ValidationGroups
{
    /**
     * The validation groups for which validation on this method should be executed. (Can be given as anonymous argument.)
     * @var array
     */
    public $validationGroups = array('Default', 'Controller');

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['validationGroups']) && is_array($values['validationGroups'])) {
            $this->validationGroups = $values['validationGroups'];
        } elseif (isset($values['value']) && is_array($values['value'])) {
            $this->validationGroups = $values['value'];
        }
    }
}

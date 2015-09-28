<?php
namespace TYPO3\Flow\Annotations;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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

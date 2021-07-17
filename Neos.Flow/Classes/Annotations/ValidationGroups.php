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
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @NamedArgumentConstructor
 * @DoctrineAnnotation\Target({"METHOD"})
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class ValidationGroups
{
    /**
     * The validation groups for which validation on this method should be executed. (Can be given as anonymous argument.)
     * @var array
     */
    public $validationGroups = ['Default', 'Controller'];

    public function __construct(array $validationGroups)
    {
        $this->validationGroups = $validationGroups;
    }
}

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

/**
 * Used to enable property injection.
 *
 * Flow will build Dependency Injection code for the property and try
 * to inject a value as specified by the var annotation.
 *
 * @Annotation
 * @Target("PROPERTY")
 */
final class Inject
{
    /**
     * Whether the dependency should be injected instantly or if a lazy dependency
     * proxy should be injected instead
     *
     * @var boolean
     */
    public $lazy = true;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['lazy'])) {
            $this->lazy = (boolean)$values['lazy'];
        }
    }
}

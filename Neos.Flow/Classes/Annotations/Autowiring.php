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
 * Used to disable autowiring for Dependency Injection on the
 * whole class or on the annotated property only.
 *
 * @Annotation
 * @Target({"METHOD", "CLASS"})
 */
final class Autowiring
{
    /**
     * Whether autowiring is enabled. (Can be given as anonymous argument.)
     * @var boolean
     */
    public $enabled = true;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (isset($values['enabled'])) {
            $this->enabled = (boolean)$values['enabled'];
        } elseif (isset($values['value'])) {
            $this->enabled = (boolean)$values['value'];
        }
    }
}

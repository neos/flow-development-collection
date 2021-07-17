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

use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * Used to control the behavior of session handling when the annotated
 * method is called.
 *
 * @Annotation
 * @NamedArgumentConstructor
 * @Target("METHOD")
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class Session
{
    /**
     * Whether the annotated method triggers the start of a session.
     * @var boolean
     */
    public $autoStart = false;

    public function __construct(bool $autoStart = false)
    {
        $this->autoStart = $autoStart;
    }
}

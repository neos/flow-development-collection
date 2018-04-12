<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A class to serialize and check if all dependencies are reinjected on unserialize.
 */
class ClassToBeSerialized
{
    /**
     * @var string
     */
    public $someProperty = 'I am not a coffee machine.';

    /**
     * @var string
     */
    protected $protectedProperty = 'I am protected.';

    /**
     * @var string
     */
    private $privateProperty = 'Saving Private Ryan.';

    /**
     * @var string
     */
    protected static $staticProperty = 'I am static';

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var PrototypeClassAishInterface
     */
    public $interfaceDeclaredSingletonButImplementationIsPrototype;

    /**
     * @Flow\Inject
     * @var SingletonClassB
     */
    public $lazyB;

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var SingletonClassC
     */
    public $eagerC;

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var PrototypeClassB
     */
    public $prototypeB;
}

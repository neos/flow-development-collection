<?php
namespace Neos\Flow\Tests\Functional\Reflection\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * @ORM\Entity
 * @Flow\Introduce("Neos\Flow\Persistence\Aspect\PersistenceMagicAspect->isEntityOrValueObject", interfaceName="Neos\Flow\Persistence\Aspect\PersistenceMagicInterface")
 */
class AnnotatedClass
{
    /**
     * @param string
     * @Flow\Introduce("within(Neos\Flow\Persistence\Aspect\PersistenceMagicInterface)")
     */
    protected $introducedProperty;

    /**
     * @param string
     * @Flow\Validate(type="Email")
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Identity
     * @ORM\Column(name="baz")
     */
    protected $mail;

    /**
     * This is the description for the regularAnnotations method.
     *
     * @param string $foo
     * @param integer $bar
     * @return integer
     * @throws \Exception
     * @api
     */
    public function regularAnnotations($foo, $bar)
    {
    }

    /**
     * @param string $foo
     * @Flow\Validate("foo", type="StringLength", options={ "mininum"=2, "maximum"=5 })
     */
    public function someFlowAnnotations($foo)
    {
    }

    /**
     * @param int $int
     * @param integer $integer
     */
    public function intAndIntegerParameters($int, $integer)
    {
    }
}

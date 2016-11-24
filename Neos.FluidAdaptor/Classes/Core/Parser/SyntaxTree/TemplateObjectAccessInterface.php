<?php
namespace Neos\FluidAdaptor\Core\Parser\SyntaxTree;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * This interface should be implemented by proxy objects which want to return
 * something different than themselves when being part of a Fluid ObjectAccess
 * chain such as {foo.bar.baz}.
 *
 * It consists only of one method "objectAccess()", which is called whenever an object
 * implementing this interface is encountered at a property path. The return value of this
 * method is the basis for further object accesses; so the object effectively "replaces" itself.
 *
 * Example: If the object at "foo.bar" implements this interface, the "objectAccess()" method
 * is called after evaluating foo.bar; and the returned value is then traversed to "baz".
 *
 * Often it can make sense to implement this interface alongside with the ArrayAccess interface.
 *
 * It is currently used *internally* and might change without further notice.
 */
interface TemplateObjectAccessInterface
{
    /**
     * Post-Processor which is called whenever this object is encountered in a Fluid
     * object access.
     *
     * @return mixed the value which should be returned to the caller, or which should be traversed further.
     */
    public function objectAccess();
}

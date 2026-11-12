<?php
namespace Neos\Flow\Tests\Functional\Mvc\Fixtures\Controller;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\ResourceManagement\PersistentResource;

/**
 * A controller with actions receiving resources, for testing that new resources
 * are discarded if a request does not complete successfully
 */
class ResourceUploadTestController extends ActionController
{
    public function initializeAction()
    {
        if ($this->arguments->hasArgument('object')) {
            $this->arguments->getArgument('object')->getPropertyMappingConfiguration()->allowAllProperties();
        }
    }

    public function successAction(PersistentResource $resource): string
    {
        return 'success:' . $resource->getSha1();
    }

    public function redirectingAction(PersistentResource $resource): void
    {
        $this->redirectToUri('http://localhost/test/mvc/resourceupload/success', 0, 303);
    }

    public function requiredArgumentAction(PersistentResource $resource, string $other): string
    {
        return 'requiredArgument';
    }

    public function throwingAction(PersistentResource $resource): string
    {
        throw new \RuntimeException('Something went wrong', 1762934401);
    }

    public function restrictedAction(PersistentResource $resource): string
    {
        return 'restricted';
    }

    public function objectAction(TestObjectWithResource $object): string
    {
        return 'object:' . $object->getResource()->getSha1();
    }
}

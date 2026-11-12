<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Security\Fixtures\Controller;

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
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\ResourceManagement\PersistentResource;
use Psr\Http\Message\UploadedFileInterface;

/**
 * A controller for functional testing of file upload
 */
class ResourceUploadController extends ActionController
{
    public function unrestrictedUploadAction(PersistentResource $resource, string $additionalParameter): string
    {
        return '';
    }

    public function arbitraryFailingUploadAction(PersistentResource $resource, string $additionalParameter): void
    {
        throw new \Exception('not going to happen');
    }

    public function restrictedUploadAction(PersistentResource $resource, string $additionalParameter): void
    {
    }

    public function initializeConstrainedUploadAction(): void
    {
        $resource = $this->request->getArgument('resource');
        if ($resource instanceof UploadedFileInterface) {
            $result = (new \finfo())->buffer((string)$resource->getStream());
            if (\str_starts_with($result, 'JPEG ')) {
                throw new \Exception('Invalid file type JPEG');
            }
        }
    }

    public function constrainedUploadAction(PersistentResource $resource, string $additionalParameter): void
    {
    }

    /**
     * @Flow\Validate(argumentName="$resource", type="FileSize", options={ "minimum"=1, "maximum"=50 })
     */
    public function validationConstrainedUploadAction(PersistentResource $resource, string $additionalParameter): void
    {
    }
}

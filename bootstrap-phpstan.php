<?php

/**
 * This bootstrap helps phpstan to detect all available constants
 */

namespace {
    $_SERVER['FLOW_ROOTPATH'] = dirname(__DIR__, 2);

    new \Neos\Flow\Core\Bootstrap('Testing');
}

// FIXME flow has no dependency on Neos.Media. This code should be extracted. https://github.com/neos/flow-development-collection/issues/3272
// Theses stubs below allow phpstan to work correctly, if Neos is not installed while linting.
namespace Neos\Media\Domain\Repository {
    if (!class_exists(AssetRepository::class)) {
        /**
         * @method iterable<int, \Neos\Media\Domain\Model\AssetInterface> findByResource(\Neos\Flow\ResourceManagement\PersistentResource $resource)
         * @method void removeWithoutUsageChecks(\Neos\Media\Domain\Model\AssetInterface $object)
         */
        class AssetRepository extends \Neos\Flow\Persistence\Repository
        {
        }
    }
}

namespace Neos\Media\Domain\Repository {
    if (!class_exists(ThumbnailRepository::class)) {
        /**
         * @method iterable<int,\Neos\Media\Domain\Model\Thumbnail> findByResource(\Neos\Flow\ResourceManagement\PersistentResource $resource)
         * @method void remove(\Neos\Media\Domain\Model\Thumbnail $object)
         */
        class ThumbnailRepository extends \Neos\Flow\Persistence\Repository
        {
        }
    }
}

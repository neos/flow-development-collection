<?php
declare(strict_types=1);

namespace Neos\Flow\Security\Cryptography;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\StringFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Algorithms as UtilityAlgorithms;

/**
 * Precomposes a hash to be used to prevent timing attacks
 *
 * @Flow\Scope("singleton")
 */
class PrecomposedHashProvider
{
    /**
     * @var HashService
     * @Flow\Inject
     */
    protected $hashService;

    /**
     * The Cache have to be injected non-lazy to prevent timing differences
     *
     * @var StringFrontend
     * @Flow\Inject(lazy=false)
     */
    protected $cache;

    public function getPrecomposedHash(): string
    {
        $hash = $this->cache->get('precomposed_hash');
        if (!$hash) {
            $hash = $this->precomposeHash();
        }

        return $hash;
    }

    public function precomposeHash(): string
    {
        $randomPassword = UtilityAlgorithms::generateRandomString(16, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789./');
        $hash = $this->hashService->hashPassword($randomPassword);
        $this->cache->set('precomposed_hash', $hash);
        return $hash;
    }
}

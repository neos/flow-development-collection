<?php
namespace Neos\Flow\Security\Authentication\Token;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\ActionRequest;

/**
 * Marker interface for authentication tokens which don't need a session.
 */
interface UsernamePasswordInterface
{
    public function getUsername(): string;

    public function getPassword(): string;
}

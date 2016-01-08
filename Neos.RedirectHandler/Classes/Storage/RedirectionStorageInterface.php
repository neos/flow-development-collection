<?php
namespace Neos\RedirectHandler\Storage;

/*
 * This file is part of the Neos.RedirectHandler package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\RedirectHandler\DatabaseStorage\Domain\Model\Redirection;
use Neos\RedirectHandler\Redirection as RedirectionDto;

/**
 * Redirection Storage Interface
 */
interface RedirectionStorageInterface
{
    /**
     * Returns one redirection for the given $sourceUriPath or NULL if it doesn't exist
     *
     * @param string $sourceUriPath
     * @param string $host Full qualified hostname or host pattern
     * @return RedirectionDto or NULL if no redirection exists for the given $sourceUriPath
     * @api
     */
    public function getOneBySourceUriPathAndHost($sourceUriPath, $host = null);

    /**
     * Returns all registered redirection records
     *
     * @param string $host Full qualified hostname or host pattern
     * @return \Generator<RedirectionDto>
     * @api
     */
    public function getAll($host = null);

    /**
     * Removes a redirection for the given $sourceUriPath if it exists
     *
     * @param string $sourceUriPath
     * @param string $host Full qualified hostname or host pattern
     * @return void
     * @api
     */
    public function removeOneBySourceUriPathAndHost($sourceUriPath, $host = null);

    /**
     * Removes all registered redirection records
     *
     * @param string $host Full qualified hostname or host pattern
     * @return void
     * @api
     */
    public function removeAll($host = null);

    /**
     * Adds a redirection to the repository and updates related redirection instances accordingly
     *
     * @param string $sourceUriPath the relative URI path that should trigger a redirect
     * @param string $targetUriPath the relative URI path the redirect should point to
     * @param integer $statusCode the status code of the redirect header
     * @param array $hostPatterns the list of host patterns
     * @return array<Redirection> the freshly generated redirection instance
     * @api
     */
    public function addRedirection($sourceUriPath, $targetUriPath, $statusCode = null, array $hostPatterns = []);
}

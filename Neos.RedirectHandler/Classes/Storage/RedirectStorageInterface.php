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
use Neos\RedirectHandler\Redirect as RedirectDto;
use Neos\RedirectHandler\RedirectInterface;

/**
 * Redirect Storage Interface
 */
interface RedirectStorageInterface
{
    /**
     * Returns one redirect DTO for the given $sourceUriPath or NULL if it doesn't exist
     *
     * @param string $sourceUriPath
     * @param string $host Full qualified host name
     * @param boolean $fallback If not redirect found, match a redirect with host value as null
     * @return RedirectDto or NULL if no redirection exists for the given $sourceUriPath
     * @api
     */
    public function getOneBySourceUriPathAndHost($sourceUriPath, $host = null, $fallback = true);

    /**
     * Returns all registered redirects
     *
     * @param string $host Full qualified host name
     * @return \Generator<RedirectDto>
     * @api
     */
    public function getAll($host = null);

    /**
     * Return a list of all hosts
     *
     * @return array
     * @api
     */
    public function getDistinctHosts();

    /**
     * Removes a redirect for the given $sourceUriPath if it exists
     *
     * @param string $sourceUriPath
     * @param string $host Full qualified host name
     * @return void
     * @api
     */
    public function removeOneBySourceUriPathAndHost($sourceUriPath, $host = null);

    /**
     * Removes all registered redirects
     *
     * @return void
     * @api
     */
    public function removeAll();

    /**
     * Removes all registered redirects by host
     *
     * @param string $host Full qualified host name
     * @return void
     * @api
     */
    public function removeByHost($host = null);

    /**
     * Adds a redirect to the repository and updates related redirects accordingly
     *
     * @param string $sourceUriPath the relative URI path that should trigger a redirect
     * @param string $targetUriPath the relative URI path the redirect should point to
     * @param integer $statusCode the status code of the redirect header
     * @param array $hosts list of full qualified host name
     * @return array<Redirect> the freshly generated redirects
     * @api
     */
    public function addRedirection($sourceUriPath, $targetUriPath, $statusCode = null, array $hosts = []);

    /**
     * Increment the hit counter for the given redirect
     *
     * @param RedirectInterface $redirect
     * @return void
     * @api
     */
    public function incrementHitCount(RedirectInterface $redirect);
}

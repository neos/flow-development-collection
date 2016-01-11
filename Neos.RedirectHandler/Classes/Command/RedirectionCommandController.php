<?php
namespace Neos\RedirectHandler\Command;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use League\Csv\Reader;
use League\Csv\Writer;
use Neos\RedirectHandler\Exception;
use Neos\RedirectHandler\Redirection;
use Neos\RedirectHandler\Service\SettingsService;
use Neos\RedirectHandler\Storage\RedirectionStorageInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\Arrays;

/**
 * Command controller for tasks related to redirects
 *
 * @Flow\Scope("singleton")
 */
class RedirectionCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var RedirectionStorageInterface
     */
    protected $redirectionStorage;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var SettingsService
     */
    protected $settingsService;

    /**
     * Save all redirectection to a CSV file
     *
     * @param string $filename CSV file path, if null use standard output
     * @param string $host Optional host pattern
     */
    public function saveCommand($filename = null, $host = null)
    {
        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        $redirections = $this->redirectionStorage->getAll($host);
        /** @var $redirection Redirection */
        foreach ($redirections as $redirection) {
            $writer->insertOne([
                $redirection->getSourceUriPath(),
                $redirection->getTargetUriPath(),
                $redirection->getStatusCode(),
                $redirection->getHostPattern()
            ]);
        }
        if ($filename === null) {
            $writer->output();
        } else {
            file_put_contents($filename, (string)$writer);
        }
    }

    /**
     * Load redirection from a CSV file
     *
     * @param string $filename CSV file path
     */
    public function loadCommand($filename)
    {
        $reader = Reader::createFromPath($filename);
        $counter = 0;
        foreach ($reader as $index => $row) {
            list($sourceUriPath, $targetUriPath, $statusCode, $hostPatterns) = $row;
            $hostPatterns = Arrays::trimExplode(',', $hostPatterns);
            foreach ($hostPatterns as $hostPattern) {
                $redirection = $this->redirectionStorage->getOneBySourceUriPathAndHost($sourceUriPath, $hostPattern);
                if ($redirection !== null && $redirection->getTargetUriPath() !== $targetUriPath && $redirection->getStatusCode() !== $statusCode) {
                    $this->outputLine('- [%d] %s', [$statusCode, $sourceUriPath]);
                    $this->redirectionStorage->removeOneBySourceUriPathAndHost($sourceUriPath, $hostPattern);
                    $this->persistenceManager->persistAll();
                }
            }
            try {
                $this->redirectionStorage->addRedirection($sourceUriPath, $targetUriPath, $statusCode, $hostPatterns);
                $this->outputLine('+ [%d] %s', [$statusCode, $sourceUriPath]);
            } catch (Exception $exception) {
                $this->outputLine('~ [%d] %s', [$statusCode, $sourceUriPath]);
            }
            $counter++;
            if ($counter % 50 === 0) {
                $this->persistenceManager->persistAll();
                $this->persistenceManager->clearState();
            }
        }
    }

    /**
     * Removes a redirection
     *
     * This command deletes a redirection from the RedirectionRepository
     *
     * @param string $sourcePath The source URI path of the redirection to remove, as given by redirect:list
     * @param string $host Full qualified hostname or host pattern
     * @return void
     */
    public function removeCommand($sourcePath, $host = null)
    {
        $redirection = $this->redirectionStorage->getOneBySourceUriPathAndHost($sourcePath, $host);
        if ($redirection === null) {
            $this->outputLine('There is no redirection with the source URI path "%s"', [$sourcePath]);
            $this->quit(1);
        }
        $this->redirectionStorage->removeOneBySourceUriPathAndHost($sourcePath, $host);
        $this->outputLine('Removed redirection with the source URI path "%s"', [$sourcePath]);
    }

    /**
     * Removes all redirections
     *
     * This command deletes all redirections from the RedirectionRepository
     *
     * @param string $host Full qualified hostname or host pattern
     * @return void
     */
    public function removeAllCommand($host = null)
    {
        $this->redirectionStorage->removeAll($host);
        if ($host === null) {
            $this->outputLine('Removed all redirections');
        } else {
            $this->outputLine('Removed all redirections for host "%s"', [$host]);
        }
    }

    /**
     * Adds a redirection
     *
     * This command adds a new redirection to the RedirectionRepository using the RedirectionService
     *
     * @param string $sourcePath The relative URI path that should trigger the redirect
     * @param string $targetPath The relative URI path that should be redirected to
     * @param integer $statusCode The status code of the redirect header
     * @param string $hostPattern Host pattern to match the redirect
     * @return void
     */
    public function addCommand($sourcePath, $targetPath, $statusCode = null, $hostPattern)
    {
        $statusCode = $statusCode ?: $this->settingsService->getRedirectStatusCode();
        $this->redirectionStorage->addRedirection($sourcePath, $targetPath, $statusCode, [$hostPattern]);
        $this->outputLine('New redirection created!');
    }
}

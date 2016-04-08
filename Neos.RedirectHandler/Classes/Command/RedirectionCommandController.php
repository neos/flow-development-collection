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
use Neos\RedirectHandler\RedirectInterface;
use Neos\RedirectHandler\Storage\RedirectStorageInterface;
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
     * @var RedirectStorageInterface
     */
    protected $redirectStorage;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @param string $host
     * @param string $match
     */
    public function listCommand($host = null, $match = null)
    {
        $outputByHost = function ($host) use ($match) {
            $redirects = $this->redirectStorage->getAll($host);
            $this->outputLine();
            if ($host !== null) {
                $this->outputLine('<info>==</info> <b>Redirection for %s</b>', [$host]);
            } else {
                $this->outputLine('<info>==</info> <b>Redirection without host attached</b>', [$host]);
            }
            if ($match !== null) {
                $this->outputLine('   <info>++</info> <b>Show only if source or target URI match <u>#%s#"</u></b>', [$match]);
            }
            $this->outputLine();
            sleep(1);
            /** @var $redirect RedirectInterface */
            foreach ($redirects as $redirect) {
                $outputLine = function ($source, $target, $statusCode) {
                    $this->outputLine('   <comment>></comment> %s <info>=></info> %s <comment>(%d)</comment>', [
                        $source,
                        $target,
                        $statusCode
                    ]);
                };
                $source = $redirect->getSourceUriPath();
                $target = $redirect->getTargetUriPath();
                $statusCode = $redirect->getStatusCode();
                if ($match === null) {
                    $outputLine($source, $target, $statusCode);
                } else {
                    $regexp = sprintf('#%s#', $match);
                    $matches = preg_grep($regexp, [$target, $source]);
                    if (count($matches) > 0) {
                        $replace = "<error>$0</error>";
                        $source = preg_replace($regexp, $replace, $source);
                        $target = preg_replace($regexp, $replace, $target);
                        $outputLine($source, $target, $statusCode);
                    }
                }
            }
        };
        if ($host !== null) {
            $outputByHost($host);
        } else {
            $hosts = $this->redirectStorage->getDistinctHosts();
            if ($hosts !== []) {
                array_map($outputByHost, $hosts);
            }
            $outputByHost($host);
        }
        $this->outputLine();
    }

    /**
     * Save all redirectection to a CSV file
     *
     * @param string $filename CSV file path, if null use standard output
     * @param string $host Optional host pattern
     * @return void
     */
    public function exportCommand($filename = null, $host = null)
    {
        if (!class_exists(Writer::class)) {
            $this->outputLine('Please run: composer require league/csv to use the export command');
            $this->sendAndExit(1);
        }
        $writer = Writer::createFromFileObject(new \SplTempFileObject());
        $redirects = $this->redirectStorage->getAll($host);
        /** @var $redirect RedirectInterface */
        foreach ($redirects as $redirect) {
            $writer->insertOne([
                $redirect->getSourceUriPath(),
                $redirect->getTargetUriPath(),
                $redirect->getStatusCode(),
                $redirect->getHost()
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
     * @return void
     */
    public function importCommand($filename)
    {
        if (!class_exists(Reader::class)) {
            $this->outputLine('Please run: composer require league/csv to use the export command');
            $this->sendAndExit(1);
        }
        $reader = Reader::createFromPath($filename);
        $counter = 0;
        foreach ($reader as $index => $row) {
            list($sourceUriPath, $targetUriPath, $statusCode, $hosts) = $row;
            $hosts = Arrays::trimExplode(',', $hosts);
            foreach ($hosts as $host) {
                $redirect = $this->redirectStorage->getOneBySourceUriPathAndHost($sourceUriPath, $host);
                if ($redirect !== null && $redirect->getTargetUriPath() !== $targetUriPath && $redirect->getStatusCode() !== $statusCode) {
                    $this->outputLine('- [%d] %s', [$statusCode, $sourceUriPath]);
                    $this->redirectStorage->removeOneBySourceUriPathAndHost($sourceUriPath, $host);
                    $this->persistenceManager->persistAll();
                }
            }
            try {
                $this->redirectStorage->addRedirection($sourceUriPath, $targetUriPath, $statusCode, $hosts);
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
     * This command deletes a redirection from the RedirectRepository
     *
     * @param string $sourcePath The source URI path of the redirection to remove, as given by redirect:list
     * @param string $host Full qualified hostname or host pattern
     * @return void
     */
    public function removeCommand($sourcePath, $host = null)
    {
        $redirect = $this->redirectStorage->getOneBySourceUriPathAndHost($sourcePath, $host);
        if ($redirect === null) {
            $this->outputLine('There is no redirection with the source URI path "%s"', [$sourcePath]);
            $this->quit(1);
        }
        $this->redirectStorage->removeOneBySourceUriPathAndHost($sourcePath, $host);
        $this->outputLine('Removed redirection with the source URI path "%s"', [$sourcePath]);
    }

    /**
     * Removes all redirections
     *
     * This command deletes all redirections from the RedirectRepository
     *
     * @param string $host Full qualified hostname or host pattern
     * @return void
     */
    public function removeAllCommand($host = null)
    {
        $this->redirectStorage->removeAll($host);
        if ($host === null) {
            $this->outputLine('Removed all redirections');
        } else {
            $this->outputLine('Removed all redirections for host "%s"', [$host]);
        }
    }

    /**
     * Adds a redirection
     *
     * This command adds a new redirection to the RedirectRepository using the RedirectionService
     *
     * @param string $sourcePath The relative URI path that should trigger the redirect
     * @param string $targetPath The relative URI path that should be redirected to
     * @param integer $statusCode The status code of the redirect header
     * @param string $host Host or host pattern to match the redirect
     * @return void
     */
    public function addCommand($sourcePath, $targetPath, $statusCode, $host = null)
    {
        $this->redirectStorage->addRedirection($sourcePath, $targetPath, $statusCode, [$host]);
        $this->outputLine('New redirection created!');
    }
}

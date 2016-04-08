<?php
namespace Neos\RedirectHandler\Tests\Functional;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\RedirectHandler\DatabaseStorage\Domain\Repository\RedirectRepository;
use Neos\RedirectHandler\RedirectionService;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the RedirectionService and dependant classes
 */
class RedirectTests extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected static $testablePersistenceEnabled = true;

    /**
     * @var RedirectionService
     */
    protected $redirectionService;

    /**
     * @var RedirectRepository
     */
    protected $redirectRepository;

    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        $this->redirectionService = $this->objectManager->get(RedirectionService::class);
        $this->redirectRepository = $this->objectManager->get(RedirectRepository::class);
    }

    /**
     * @test
     */
    public function addRedirectTrimsLeadingAndTrailingSlashesOfSourceAndTargetPath()
    {
        $this->assertEquals(0, $this->redirectRepository->countAll());
        $this->redirectionService->addRedirection('/some/source/path/', '/some/target/path/');

        $this->persistenceManager->persistAll();
        $redirect = $this->redirectRepository->findAll()->getFirst();

        $this->assertSame('some/source/path', $redirect->getSourceUriPath());
        $this->assertSame('some/target/path', $redirect->getTargetUriPath());
    }

    /**
     * @test
     */
    public function addRedirectSetsTheCorrectDefaultStatusCode()
    {
        $this->assertEquals(0, $this->redirectRepository->countAll());
        $this->redirectionService->addRedirection('some/source/path', 'some/target/path');

        $this->persistenceManager->persistAll();
        $redirect = $this->redirectRepository->findAll()->getFirst();

        $this->assertSame(301, $redirect->getStatusCode());
    }

    /**
     * @test
     */
    public function addRedirectRespectsTheGivenStatusCode()
    {
        $this->assertEquals(0, $this->redirectRepository->countAll());
        $this->redirectionService->addRedirection('some/source/path', 'some/target/path', 123);

        $this->persistenceManager->persistAll();
        $redirect = $this->redirectRepository->findAll()->getFirst();

        $this->assertSame(123, $redirect->getStatusCode());
    }

    /**
     * @test
     * @expectedException \Neos\RedirectHandler\Exception
     */
    public function addRedirectThrowsExceptionIfARedirectExistsForTheGivenSourceUriPath()
    {
        $this->redirectionService->addRedirection('a', 'b');
        $this->redirectionService->addRedirection('c', 'd');
        $this->persistenceManager->persistAll();

        $this->redirectionService->addRedirection('c', 'e');
    }

    /**
     * @test
     * @expectedException \Neos\RedirectHandler\Exception
     */
    public function addRedirectThrowsExceptionIfARedirectExistsForTheGivenTargetUriPath()
    {
        $this->redirectionService->addRedirection('a', 'b');
        $this->redirectionService->addRedirection('c', 'd');
        $this->persistenceManager->persistAll();

        $this->redirectionService->addRedirection('b', 'c');
    }

    /**
     * @test
     */
    public function addRedirectDoesNotThrowAnExceptionIfARedirectReversesAnExistingRedirect()
    {
        $this->redirectionService->addRedirection('a', 'b');
        $this->redirectionService->addRedirection('c', 'd');
        $this->persistenceManager->persistAll();

        $this->redirectionService->addRedirection('d', 'c');
        $this->persistenceManager->persistAll();

        $expectedRedirects = array('a' => 'b', 'd' => 'c');

        $resultingRedirects = array();
        foreach ($this->redirectRepository->findAll() as $redirect) {
            $resultingRedirects[$redirect->getSourceUriPath()] = $redirect->getTargetUriPath();
        }
        $this->assertSame($expectedRedirects, $resultingRedirects);
    }

    /**
     * Data provider for addRedirectTests()
     */
    public function addRedirectDataProvider()
    {
        return array(
            // avoid redundant redirects (c -> d gets updated to c -> e)
            array(
                'existingRedirects' => array(
                    'a' => 'b',
                    'c' => 'd',
                ),
                'newRedirects' => array(
                    'd' => 'e',
                ),
                'expectedRedirects' => array(
                    'a' => 'b',
                    'c' => 'e',
                    'd' => 'e',
                ),
            ),
            // avoid redundant redirects, recursively (c -> d gets updated to c -> e)
            array(
                'existingRedirects' => array(
                    'a' => 'b',
                    'c' => 'b',
                ),
                'newRedirects' => array(
                    'b' => 'd',
                ),
                'expectedRedirects' => array(
                    'a' => 'd',
                    'b' => 'd',
                    'c' => 'd',
                ),
            ),
            // avoid circular redirects (c -> d is replaced by d -> c)
            array(
                'existingRedirects' => array(
                    'a' => 'b',
                    'c' => 'd',
                ),
                'newRedirects' => array(
                    'd' => 'c',
                ),
                'expectedRedirects' => array(
                    'a' => 'b',
                    'd' => 'c',
                ),
            ),
        );
    }

    /**
     * @test
     * @dataProvider addRedirectDataProvider
     * 
     * @param array $existingRedirects
     * @param array $newRedirects
     * @param array $expectedRedirects
     */
    public function addRedirectTests(array $existingRedirects, array $newRedirects, array $expectedRedirects)
    {
        foreach ($existingRedirects as $sourceUriPath => $targetUriPath) {
            $this->redirectionService->addRedirection($sourceUriPath, $targetUriPath);
        }
        $this->persistenceManager->persistAll();

        foreach ($newRedirects as $sourceUriPath => $targetUriPath) {
            $this->redirectionService->addRedirection($sourceUriPath, $targetUriPath);
        }
        $this->persistenceManager->persistAll();

        $resultingRedirects = array();
        foreach ($this->redirectRepository->findAll() as $redirect) {
            $resultingRedirects[$redirect->getSourceUriPath()] = $redirect->getTargetUriPath();
        }
        $this->assertSame($expectedRedirects, $resultingRedirects);
    }
}

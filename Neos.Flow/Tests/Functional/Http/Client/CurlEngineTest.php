<?php
namespace Neos\Flow\Tests\Functional\Http\Client;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Client\CurlEngine;
use Neos\Flow\Tests\FunctionalTestCase;

/**
 * Functional tests for the HTTP client internal request engine
 *
 * @requires extension curl
 */
class CurlEngineTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $curlEngine = $this->objectManager->get(CurlEngine::class);
        $this->browser->setRequestEngine($curlEngine);
    }

    /**
     * Check if the Curl Engine can send a GET request to www.neos.io
     *
     * @test
     */
    public function getRequestReturnsResponse()
    {
        $response = $this->browser->request('http://www.neos.io');
        $this->assertContains('This website is powered by Neos', $response->getContent());
    }
}

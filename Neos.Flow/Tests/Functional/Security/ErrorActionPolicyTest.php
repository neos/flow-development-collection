<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Security;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * Functional tests which make sure that the error action, which is called instead of the requested
 * action if validation fails, is only called if the requested action is accessible.
 */
class ErrorActionPolicyTest extends FunctionalTestCase
{
    /**
     * Code of the exception thrown by ErrorActionPolicyTestController::errorAction()
     */
    protected const ERROR_ACTION_CALLED = '1794502055';

    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerRoute('erroractionpolicy', 'test/security/erroractionpolicy(/{@action})', [
            '@package' => 'Neos.Flow',
            '@subpackage' => 'Tests\Functional\Security\Fixtures',
            '@controller' => 'ErrorActionPolicyTest',
            '@action' => 'public',
            '@format' => 'html'
        ]);
    }

    protected function post(string $action, string $emailAddress): ResponseInterface
    {
        return $this->browser->request('http://localhost/test/security/erroractionpolicy/' . $action, 'POST', ['emailAddress' => $emailAddress]);
    }

    protected function exceptionCode(string $body): string
    {
        self::assertSame(1, preg_match('/Uncaught Exception in Flow #(\d+)/', $body, $matches), 'No exception was rendered: ' . $body);
        return $matches[1];
    }

    #[Test]
    public function errorActionIsCalledForAPublicActionIfValidationFails(): void
    {
        $response = $this->post('public', 'invalid');
        self::assertSame(self::ERROR_ACTION_CALLED, $this->exceptionCode($response->getBody()->getContents()));
    }

    #[Test]
    public function errorActionIsNotCalledForARestrictedActionIfNobodyIsAuthenticated(): void
    {
        $responseForValidArguments = $this->post('restricted', 'someone@example.com');
        $bodyForValidArguments = $responseForValidArguments->getBody()->getContents();
        self::assertNotSame('restricted', $bodyForValidArguments);

        $response = $this->post('restricted', 'invalid');
        $body = $response->getBody()->getContents();
        self::assertNotSame(self::ERROR_ACTION_CALLED, $this->exceptionCode($body), 'The error action was called');
        // The request is denied in the same way as if the action method itself had been called
        self::assertSame($responseForValidArguments->getStatusCode(), $response->getStatusCode());
        self::assertSame($this->exceptionCode($bodyForValidArguments), $this->exceptionCode($body));
    }

    #[Test]
    public function errorActionIsNotCalledForARestrictedActionIfAccessIsDenied(): void
    {
        $this->authenticateRoles(['Neos.Flow:Administrator']);

        $responseForValidArguments = $this->post('restricted', 'someone@example.com');
        $bodyForValidArguments = $responseForValidArguments->getBody()->getContents();
        self::assertNotSame('restricted', $bodyForValidArguments);

        $response = $this->post('restricted', 'invalid');
        $body = $response->getBody()->getContents();
        self::assertNotSame(self::ERROR_ACTION_CALLED, $this->exceptionCode($body), 'The error action was called');
        // The request is denied in the same way as if the action method itself had been called
        self::assertSame($responseForValidArguments->getStatusCode(), $response->getStatusCode());
        self::assertSame($this->exceptionCode($bodyForValidArguments), $this->exceptionCode($body));
    }

    #[Test]
    public function errorActionIsCalledForARestrictedActionIfAccessIsGranted(): void
    {
        $this->authenticateRoles(['Neos.Flow:Customer']);

        $response = $this->post('restricted', 'invalid');
        self::assertSame(self::ERROR_ACTION_CALLED, $this->exceptionCode($response->getBody()->getContents()));
    }

    #[Test]
    public function restrictedActionIsCalledIfAccessIsGrantedAndValidationSucceeds(): void
    {
        $this->authenticateRoles(['Neos.Flow:Customer']);

        $response = $this->post('restricted', 'someone@example.com');
        self::assertSame('restricted', $response->getBody()->getContents());
    }
}

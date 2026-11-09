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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Account;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the account factory
 *
 */
final class AccountTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @var Account
     */
    protected $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = $this->objectManager->get(Account::class);
    }

    #[Test]
    public function freshAccountIsActive()
    {
        $this->account->setExpirationDate(null);
        self::assertTrue($this->account->isActive());
    }

    #[Test]
    public function expiredAccountIsInActive()
    {
        $this->account->setExpirationDate((new \DateTime("now"))->sub(new \DateInterval("PT1H")));
        self::assertFalse($this->account->isActive());
    }

    #[Test]
    public function notYetExpiredAccountIsInActive()
    {
        $this->account->setExpirationDate((new \DateTime("now"))->add(new \DateInterval("PT1H")));
        self::assertTrue($this->account->isActive());
    }
}

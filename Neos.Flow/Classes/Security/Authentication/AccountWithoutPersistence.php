<?php

namespace Neos\Flow\Security\Authentication;

use Neos\Flow\Security\Account;

/**
 * This is an account, which is no entity - useful to be used in sessionless tokens, or tokens, which can create
 * their own Account
 */
class AccountWithoutPersistence extends Account
{
}

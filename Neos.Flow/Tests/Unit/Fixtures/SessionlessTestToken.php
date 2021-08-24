<?php
namespace Neos\Flow\Fixtures;

use Neos\Flow\Security\Authentication\Token\SessionlessTokenInterface;
use Neos\Flow\Security\Authentication\TokenInterface;

abstract class SessionlessTestToken implements TokenInterface, SessionlessTokenInterface
{
}

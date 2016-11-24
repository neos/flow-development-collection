<?php
namespace Neos\Flow\Security\Authorization;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * An access decision manager that can be overridden for tests
 *
 * @Flow\Scope("singleton")
 */
class TestingPrivilegeManager extends PrivilegeManager
{
    /**
     * @var boolean
     */
    protected $overrideDecision = null;

    /**
     * Returns TRUE, if the given privilege type is granted for the given subject based
     * on the current security context or if set based on the override decision value.
     *
     * @param string $privilegeType
     * @param mixed $subject
     * @param string $reason This variable will be filled by a message giving information about the reasons for the result of this method
     * @return boolean
     */
    public function isGranted($privilegeType, $subject, &$reason = '')
    {
        if ($this->overrideDecision === false) {
            $reason = 'Voting has been overriden to "DENY" by the testing privilege manager!';
            return false;
        } elseif ($this->overrideDecision === true) {
            $reason = 'Voting has been overriden to "GRANT" by the testing privilege manager!';
            return true;
        }
        return parent::isGranted($privilegeType, $subject, $reason);
    }

    /**
     * Returns TRUE if access is granted on the given privilege target in the current security context
     * or if set based on the override decision value.
     *
     * @param string $privilegeTargetIdentifier The identifier of the privilege target to decide on
     * @param array $privilegeParameters Optional array of privilege parameters (simple key => value array)
     * @return boolean TRUE if access is granted, FALSE otherwise
     */
    public function isPrivilegeTargetGranted($privilegeTargetIdentifier, array $privilegeParameters = [])
    {
        if ($this->overrideDecision === false) {
            return false;
        } elseif ($this->overrideDecision === true) {
            return true;
        }
        return parent::isPrivilegeTargetGranted($privilegeTargetIdentifier, $privilegeParameters);
    }

    /**
     * Set the decision override
     *
     * @param boolean $overrideDecision TRUE or FALSE to override the decision, NULL to use the access decision voter manager
     * @return void
     */
    public function setOverrideDecision($overrideDecision)
    {
        $this->overrideDecision = $overrideDecision;
    }

    /**
     * Resets the AccessDecisionManager to behave transparently.
     *
     * @return void
     */
    public function reset()
    {
        $this->overrideDecision = null;
    }
}

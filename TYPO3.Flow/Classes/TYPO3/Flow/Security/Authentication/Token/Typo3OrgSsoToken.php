<?php
namespace TYPO3\Flow\Security\Authentication\Token;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * An authentication token used for sso credentials coming from typo3.org
 */
class Typo3OrgSsoToken extends \TYPO3\Flow\Security\Authentication\Token\AbstractToken
{
    /**
     * The username/password credentials
     * @var array
     * @Flow\Transient
     */
    protected $credentials = array('username' => '', 'signature' => '');

    /**
     * @var \TYPO3\Flow\Utility\Environment
     * @Flow\Inject
     */
    protected $environment;

    /**
     * Updates the username and password credentials from the POST vars, if the POST parameters
     * are available. Sets the authentication status to REAUTHENTICATION_NEEDED, if credentials have been sent.
     *
     * @param \TYPO3\Flow\Mvc\ActionRequest $actionRequest The current action request instance
     * @return void
     */
    public function updateCredentials(\TYPO3\Flow\Mvc\ActionRequest $actionRequest)
    {
        $getArguments = $actionRequest->getArguments();

        if (!empty($getArguments['user'])
            && !empty($getArguments['signature'])
            && !empty($getArguments['expires'])
            && !empty($getArguments['version'])
            && !empty($getArguments['tpa_id'])
            && !empty($getArguments['action'])
            && !empty($getArguments['flags'])
            && !empty($getArguments['userdata'])) {
            $this->credentials['username'] = $getArguments['user'];
            $this->credentials['signature'] = \TYPO3\Flow\Utility\TypeHandling::hex2bin($getArguments['signature']);
            $this->credentials['expires'] = $getArguments['expires'];
            $this->credentials['version'] = $getArguments['version'];
            $this->credentials['tpaId'] = $getArguments['tpa_id'];
            $this->credentials['action'] = $getArguments['action'];
            $this->credentials['flags'] = $getArguments['flags'];
            $this->credentials['userdata'] = $getArguments['userdata'];

            $this->setAuthenticationStatus(self::AUTHENTICATION_NEEDED);
        }
    }

    /**
     * Returns a string representation of the token for logging purposes.
     *
     * @return string The username credential
     */
    public function __toString()
    {
        return 'Username: "' . $this->credentials['username'] . '"';
    }
}

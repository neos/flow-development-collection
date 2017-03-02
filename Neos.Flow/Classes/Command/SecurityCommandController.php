<?php
namespace Neos\Flow\Command;

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
use Neos\Flow\Cache\CacheManager;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Mvc\Controller\AbstractController;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Security\Cryptography\RsaWalletServicePhp;
use Neos\Flow\Security\Exception\NoSuchRoleException;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use Neos\Flow\Security\Policy\Role;
use Neos\Utility\Arrays;

/**
 * Command controller for tasks related to security
 *
 * @Flow\Scope("singleton")
 */
class SecurityCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var RsaWalletServicePhp
     */
    protected $rsaWalletService;

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @var VariableFrontend
     */
    protected $methodPermissionCache;

    /**
     * Injects the Cache Manager because we cannot inject an automatically factored cache during compile time.
     *
     * @param CacheManager $cacheManager
     * @return void
     */
    public function injectCacheManager(CacheManager $cacheManager)
    {
        $this->methodPermissionCache = $cacheManager->getCache('Flow_Security_Authorization_Privilege_Method');
    }

    /**
     * Import a public key
     *
     * Read a PEM formatted public key from stdin and import it into the
     * RSAWalletService.
     *
     * @return void
     * @see neos.flow:security:importprivatekey
     */
    public function importPublicKeyCommand()
    {
        $keyData = '';
        // no file_get_contents here because it does not work on php://stdin
        $fp = fopen('php://stdin', 'rb');
        while (!feof($fp)) {
            $keyData .= fgets($fp, 4096);
        }
        fclose($fp);

        $fingerprint = $this->rsaWalletService->registerPublicKeyFromString($keyData);

        $this->outputLine('The public key has been successfully imported. Use the following fingerprint to refer to it in the RSAWalletService: ' . PHP_EOL . PHP_EOL . $fingerprint . PHP_EOL);
    }

    /**
     * Generate a public/private key pair and add it to the RSAWalletService
     *
     * @param boolean $usedForPasswords If the private key should be used for passwords
     * @return void
     * @see neos.flow:security:importprivatekey
     */
    public function generateKeyPairCommand($usedForPasswords = false)
    {
        $fingerprint = $this->rsaWalletService->generateNewKeypair($usedForPasswords);

        $this->outputLine('The key pair has been successfully generated. Use the following fingerprint to refer to it in the RSAWalletService: ' . PHP_EOL . PHP_EOL . $fingerprint . PHP_EOL);
    }

    /**
     * Import a private key
     *
     * Read a PEM formatted private key from stdin and import it into the
     * RSAWalletService. The public key will be automatically extracted and stored
     * together with the private key as a key pair.
     *
     * You can generate the same fingerprint returned from this using these commands:
     *
     *  ssh-keygen -yf my-key.pem > my-key.pub
     *  ssh-keygen -lf my-key.pub
     *
     * To create a private key to import using this method, you can use:
     *
     *  ssh-keygen -t rsa -f my-key
     *  ./flow security:importprivatekey < my-key
     *
     * Again, the fingerprint can also be generated using:
     *
     *  ssh-keygen -lf my-key.pub
     *
     * @param boolean $usedForPasswords If the private key should be used for passwords
     * @return void
     * @see neos.flow:security:importpublickey
     * @see neos.flow:security:generatekeypair
     */
    public function importPrivateKeyCommand($usedForPasswords = false)
    {
        $keyData = '';
        // no file_get_contents here because it does not work on php://stdin
        $fp = fopen('php://stdin', 'rb');
        while (!feof($fp)) {
            $keyData .= fgets($fp, 4096);
        }
        fclose($fp);

        $fingerprint = $this->rsaWalletService->registerKeyPairFromPrivateKeyString($keyData, $usedForPasswords);

        $this->outputLine('The keypair has been successfully imported. Use the following fingerprint to refer to it in the RSAWalletService: ' . PHP_EOL . PHP_EOL . $fingerprint . PHP_EOL);
    }

    /**
     * Shows a list of all defined privilege targets and the effective permissions
     *
     * @param string $privilegeType The privilege type ("entity", "method" or the FQN of a class implementing PrivilegeInterface)
     * @param string $roles A comma separated list of role identifiers. Shows policy for an unauthenticated user when left empty.
     */
    public function showEffectivePolicyCommand($privilegeType, $roles = '')
    {
        $systemRoleIdentifiers = ['Neos.Flow:Everybody', 'Neos.Flow:Anonymous', 'Neos.Flow:AuthenticatedUser'];

        if (strpos($privilegeType, '\\') === false) {
            $privilegeType = sprintf('\Neos\Flow\Security\Authorization\Privilege\%s\%sPrivilegeInterface', ucfirst($privilegeType), ucfirst($privilegeType));
        }
        if (!class_exists($privilegeType) && !interface_exists($privilegeType)) {
            $this->outputLine('The privilege type "%s" was not defined.', [$privilegeType]);
            $this->quit(1);
        }
        if (!is_subclass_of($privilegeType, PrivilegeInterface::class)) {
            $this->outputLine('"%s" does not refer to a valid Privilege', [$privilegeType]);
            $this->quit(1);
        }

        $requestedRoles = [];
        foreach (Arrays::trimExplode(',', $roles) as $roleIdentifier) {
            try {
                if (in_array($roleIdentifier, $systemRoleIdentifiers)) {
                    continue;
                }
                $currentRole = $this->policyService->getRole($roleIdentifier);
                $requestedRoles[$roleIdentifier] = $currentRole;
                foreach ($currentRole->getAllParentRoles() as $currentParentRole) {
                    if (!in_array($currentParentRole, $requestedRoles)) {
                        $requestedRoles[$currentParentRole->getIdentifier()] = $currentParentRole;
                    }
                }
            } catch (NoSuchRoleException $exception) {
                $this->outputLine('The role %s was not defined.', [$roleIdentifier]);
                $this->quit(1);
            }
        }
        if (count($requestedRoles) > 0) {
            $requestedRoles['Neos.Flow:AuthenticatedUser'] = $this->policyService->getRole('Neos.Flow:AuthenticatedUser');
        } else {
            $requestedRoles['Neos.Flow:Anonymous'] = $this->policyService->getRole('Neos.Flow:Anonymous');
        }
        $requestedRoles['Neos.Flow:Everybody'] = $this->policyService->getRole('Neos.Flow:Everybody');

        $this->outputLine('Effective Permissions for the roles <b>%s</b> ', [implode(', ', $requestedRoles)]);
        $this->outputLine(str_repeat('-', $this->output->getMaximumLineLength()));

        $definedPrivileges = $this->policyService->getAllPrivilegesByType($privilegeType);
        $permissions = [];

        /** @var PrivilegeInterface $definedPrivilege */
        foreach ($definedPrivileges as $definedPrivilege) {
            $accessGrants = 0;
            $accessDenies = 0;

            $permission = 'ABSTAIN';

            /** @var Role $requestedRole */
            foreach ($requestedRoles as $requestedRole) {
                $privilegeType = $requestedRole->getPrivilegeForTarget($definedPrivilege->getPrivilegeTarget()->getIdentifier());

                if ($privilegeType === null) {
                    continue;
                }

                if ($privilegeType->isGranted()) {
                    $accessGrants++;
                } elseif ($privilegeType->isDenied()) {
                    $accessDenies++;
                }
            }

            if ($accessDenies > 0) {
                $permission = '<error>DENY</error>';
            }
            if ($accessGrants > 0 && $accessDenies === 0) {
                $permission = '<success>GRANT</success>';
            }

            $permissions[$definedPrivilege->getPrivilegeTarget()->getIdentifier()] = $permission;
        }

        ksort($permissions);

        foreach ($permissions as $privilegeTargetIdentifier => $permission) {
            $formattedPrivilegeTargetIdentifier = wordwrap($privilegeTargetIdentifier, $this->output->getMaximumLineLength() - 10, PHP_EOL . str_repeat(' ', 10), true);
            $this->outputLine('%-70s %s', [$formattedPrivilegeTargetIdentifier, $permission]);
        }
    }

    /**
     * Lists all public controller actions not covered by the active security policy
     *
     * @return void
     */
    public function showUnprotectedActionsCommand()
    {
        $methodPrivileges = [];
        foreach ($this->policyService->getRoles(true) as $role) {
            $methodPrivileges = array_merge($methodPrivileges, $role->getPrivilegesByType(MethodPrivilegeInterface::class));
        }

        $controllerClassNames = $this->reflectionService->getAllSubClassNamesForClass(AbstractController::class);
        $allActionsAreProtected = true;
        foreach ($controllerClassNames as $controllerClassName) {
            if ($this->reflectionService->isClassAbstract($controllerClassName)) {
                continue;
            }

            $methodNames = get_class_methods($controllerClassName);
            $foundUnprotectedAction = false;
            foreach ($methodNames as $methodName) {
                if (preg_match('/.*Action$/', $methodName) === 0 || $this->reflectionService->isMethodPublic($controllerClassName, $methodName) === false) {
                    continue;
                }
                /** @var MethodPrivilegeInterface $methodPrivilege */
                foreach ($methodPrivileges as $methodPrivilege) {
                    if ($methodPrivilege->matchesMethod($controllerClassName, $methodName)) {
                        continue 2;
                    }
                }

                if ($foundUnprotectedAction === false) {
                    $this->outputLine(PHP_EOL . '<b>' . $controllerClassName . '</b>');
                    $foundUnprotectedAction = true;
                    $allActionsAreProtected = false;
                }
                $this->outputLine('  ' . $methodName);
            }
        }

        if ($allActionsAreProtected === true) {
            $this->outputLine('All public controller actions are covered by your security policy. Good job!');
        }
    }

    /**
     * Shows the methods represented by the given security privilege target
     *
     * If the privilege target has parameters those can be specified separated by a colon
     * for example "parameter1:value1" "parameter2:value2".
     * But be aware that this only works for parameters that have been specified in the policy
     *
     * @param string $privilegeTarget The name of the privilegeTarget as stated in the policy
     * @return void
     */
    public function showMethodsForPrivilegeTargetCommand($privilegeTarget)
    {
        $privilegeTargetInstance = $this->policyService->getPrivilegeTargetByIdentifier($privilegeTarget);
        if ($privilegeTargetInstance === null) {
            $this->outputLine('The privilegeTarget "%s" is not defined', [$privilegeTarget]);
            $this->quit(1);
        }
        $privilegeParameters = [];
        foreach ($this->request->getExceedingArguments() as $argument) {
            list($argumentName, $argumentValue) = explode(':', $argument, 2);
            $privilegeParameters[$argumentName] = $argumentValue;
        }
        $privilege = $privilegeTargetInstance->createPrivilege(PrivilegeInterface::GRANT, $privilegeParameters);
        if (!$privilege instanceof MethodPrivilegeInterface) {
            $this->outputLine('The privilegeTarget "%s" does not refer to a MethodPrivilege but to a privilege of type "%s"', [$privilegeTarget, $privilege->getPrivilegeTarget()->getPrivilegeClassName()]);
            $this->quit(1);
        }

        $matchedClassesAndMethods = [];
        foreach ($this->reflectionService->getAllClassNames() as $className) {
            try {
                $reflectionClass = new \ReflectionClass($className);
            } catch (\ReflectionException $exception) {
                continue;
            }
            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                $methodName = $reflectionMethod->getName();
                if ($privilege->matchesMethod($className, $methodName)) {
                    $matchedClassesAndMethods[$className][$methodName] = $methodName;
                }
            }
        }

        if (count($matchedClassesAndMethods) === 0) {
            $this->outputLine('The given privilegeTarget did not match any method.');
            $this->quit(1);
        }

        foreach ($matchedClassesAndMethods as $className => $methods) {
            $this->outputLine($className);
            foreach ($methods as $methodName) {
                $this->outputLine('  ' . $methodName);
            }
            $this->outputLine();
        }
    }
}

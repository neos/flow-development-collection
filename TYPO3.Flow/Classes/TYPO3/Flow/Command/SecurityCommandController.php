<?php
namespace TYPO3\Flow\Command;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cache\CacheManager;
use TYPO3\Flow\Cache\Frontend\VariableFrontend;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Security\Authorization\PrivilegeVoteResult;
use TYPO3\Flow\Security\Cryptography\RsaWalletServicePhp;
use TYPO3\Flow\Security\Exception\NoSuchRoleException;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\Flow\Utility\Arrays;

/**
 * Command controller for tasks related to security
 *
 * @Flow\Scope("singleton")
 */
class SecurityCommandController extends CommandController {

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
	public function injectCacheManager(CacheManager $cacheManager) {
		$this->methodPermissionCache = $cacheManager->getCache('Flow_Security_Authorization_Privilege_Method');
	}

	/**
	 * Import a public key
	 *
	 * Read a PEM formatted public key from stdin and import it into the
	 * RSAWalletService.
	 *
	 * @return void
	 * @see typo3.flow:security:importprivatekey
	 */
	public function importPublicKeyCommand() {
		$keyData = '';
		// no file_get_contents here because it does not work on php://stdin
		$fp = fopen('php://stdin', 'rb');
		while (!feof($fp)) {
			$keyData .= fgets($fp, 4096);
		}
		fclose($fp);

		$uuid = $this->rsaWalletService->registerPublicKeyFromString($keyData);

		$this->outputLine('The public key has been successfully imported. Use the following uuid to refer to it in the RSAWalletService: ' . PHP_EOL . PHP_EOL . $uuid . PHP_EOL);
	}

	/**
	 * Import a private key
	 *
	 * Read a PEM formatted private key from stdin and import it into the
	 * RSAWalletService. The public key will be automatically extracted and stored
	 * together with the private key as a key pair.
	 *
	 * @param boolean $usedForPasswords If the private key should be used for passwords
	 * @return void
	 * @see typo3.flow:security:importpublickey
	 */
	public function importPrivateKeyCommand($usedForPasswords = FALSE) {
		$keyData = '';
		// no file_get_contents here because it does not work on php://stdin
		$fp = fopen('php://stdin', 'rb');
		while (!feof($fp)) {
			$keyData .= fgets($fp, 4096);
		}
		fclose($fp);

		$uuid = $this->rsaWalletService->registerKeyPairFromPrivateKeyString($keyData, $usedForPasswords);

		$this->outputLine('The keypair has been successfully imported. Use the following uuid to refer to it in the RSAWalletService: ' . PHP_EOL . PHP_EOL . $uuid . PHP_EOL);
	}

	/**
	 * Shows a list of all defined privilege targets and the effective permissions for the given groups.
	 *
	 * @param string $privilegeType The privilege type (entity or method)
	 * @param string $roleIdentifiers A comma separated list of roleIdentifiers. Shows policy for an unauthenticated user when left empty.
	 */
	public function showEffectivePolicyCommand($privilegeType, $roleIdentifiers = '') {

		$systemRoleIdentifiers = array('TYPO3.Flow:Everybody', 'TYPO3.Flow:Anonymous', 'TYPO3.Flow:AuthenticatedUser');

		if(interface_exists($privilegeType)) {
			$privilegeTypeInterfaceName = $privilegeType;
		} else {
			$privilegeTypeInterfaceName = sprintf('\TYPO3\Flow\Security\Authorization\Privilege\%s\%sPrivilegeInterface', ucfirst(strtolower($privilegeType)), ucfirst(strtolower($privilegeType)));
		}
		if(!interface_exists($privilegeTypeInterfaceName)) {
			$this->outputLine('The privilege type %s was not defined.', array($privilegeTypeInterfaceName));
			$this->quit(1);
		}

		$requestedRoles = array();
		foreach (Arrays::trimExplode(',', $roleIdentifiers) as $roleIdentifier) {
			try {
				if(!in_array($roleIdentifier, $systemRoleIdentifiers)) {
					$requestedRoles[$roleIdentifier] = $this->policyService->getRole($roleIdentifier);
				}
			} catch (NoSuchRoleException $exception) {
				$this->outputLine('The role %s was not defined.', array($roleIdentifier));
				$this->quit(1);
			}
		}
		if(count($requestedRoles) > 0) {
			$requestedRoles['TYPO3.Flow:AuthenticatedUser'] = $this->policyService->getRole('TYPO3.Flow:AuthenticatedUser');
		} else {
			$requestedRoles['TYPO3.Flow:Anonymous'] = $this->policyService->getRole('TYPO3.Flow:Anonymous');
		}
		$requestedRoles['TYPO3.Flow:Everybody'] = $this->policyService->getRole('TYPO3.Flow:Everybody');

		$this->outputLine('Effective Permissions for the roles <b>%s</b> ', array(implode(', ', $requestedRoles)));
		$this->outputLine(str_repeat('-', $this->output->getMaximumLineLength()));

		$definedPrivileges = $this->policyService->getAllPrivilegesByType($privilegeTypeInterfaceName);
		$permissions = array();

		/** @var PrivilegeInterface $definedPrivilege */
		foreach($definedPrivileges as $definedPrivilege) {

			$accessGrants = 0;
			$accessDenies = 0;

			$permission = sprintf('%s', PrivilegeVoteResult::VOTE_ABSTAIN);

			/** @var Role $requestedRole */
			foreach($requestedRoles as $requestedRole) {
				$privilege = $requestedRole->getPrivilegeForTarget($definedPrivilege->getPrivilegeTarget()->getIdentifier());

				if ($privilege === NULL) {
					continue;
				}

				if ($privilege->isGranted()) {
					$accessGrants++;
				} elseif ($privilege->isDenied()) {
					$accessDenies++;
				}
			}

			if ($accessDenies > 0) {
				$permission = sprintf('<error>%s</error>', PrivilegeVoteResult::VOTE_DENY);
			}
			if ($accessGrants > 0 && $accessDenies === 0) {
				$permission = sprintf('<success>%s</success>', PrivilegeVoteResult::VOTE_GRANT);
			}

			$permissions[$definedPrivilege->getPrivilegeTarget()->getIdentifier()] = $permission;
		}

		ksort($permissions);

		foreach($permissions as $privilegeTargetIdentifier => $permission) {
			$formattedPrivilegeTargetIdentifier = wordwrap($privilegeTargetIdentifier, $this->output->getMaximumLineLength() - 10, PHP_EOL . str_repeat(' ', 10), TRUE);
			$this->outputLine('%-70s %s', array($formattedPrivilegeTargetIdentifier, $permission));
		}
	}

	/**
	 * Lists all public controller actions not covered by the active security policy
	 *
	 * @return void
	 */
	public function showUnprotectedActionsCommand() {
		$methodPrivileges = array();
		foreach ($this->policyService->getRoles(TRUE) as $role) {
			$methodPrivileges = array_merge($methodPrivileges, $role->getPrivilegesByType('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface'));
		}

		$controllerClassNames = $this->reflectionService->getAllSubClassNamesForClass('TYPO3\Flow\Mvc\Controller\AbstractController');
		$allActionsAreProtected = TRUE;
		foreach ($controllerClassNames as $controllerClassName) {
			if ($this->reflectionService->isClassAbstract($controllerClassName)) {
				continue;
			}

			$methodNames = get_class_methods($controllerClassName);
			$foundUnprotectedAction = FALSE;
			foreach ($methodNames as $methodName) {
				if (preg_match('/.*Action$/', $methodName) === 0 || $this->reflectionService->isMethodPublic($controllerClassName, $methodName) === FALSE) {
					continue;
				}
				/** @var MethodPrivilegeInterface $methodPrivilege */
				foreach ($methodPrivileges as $methodPrivilege) {
					if ($methodPrivilege->matchesMethod($controllerClassName, $methodName)) {
						continue 2;
					}
				}

				if ($foundUnprotectedAction === FALSE) {
					$this->outputLine(PHP_EOL . '<b>' . $controllerClassName . '</b>');
					$foundUnprotectedAction = TRUE;
					$allActionsAreProtected = FALSE;
				}
				$this->outputLine('  ' . $methodName);
			}
		}

		if ($allActionsAreProtected === TRUE) {
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
	public function showMethodsForPrivilegeTargetCommand($privilegeTarget) {
		$privilegeTargetInstance = $this->policyService->getPrivilegeTargetByIdentifier($privilegeTarget);
		if ($privilegeTargetInstance === NULL) {
			$this->outputLine('The privilegeTarget "%s" is not defined', array($privilegeTarget));
			$this->quit(1);
		}
		$privilegeParameters = array();
		foreach ($this->request->getExceedingArguments() as $argument) {
			list($argumentName, $argumentValue) = explode(':', $argument, 2);
			$privilegeParameters[$argumentName] = $argumentValue;
		}
		$privilege = $privilegeTargetInstance->createPrivilege(PrivilegeInterface::GRANT, $privilegeParameters);
		if (!$privilege instanceof MethodPrivilegeInterface) {
			$this->outputLine('The privilegeTarget "%s" does not refer to a MethodPrivilege but to a privilege of type "%s"', array($privilegeTarget, $privilege->getPrivilegeTarget()->getPrivilegeClassName()));
			$this->quit(1);
		}

		$matchedClassesAndMethods = array();
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
			$this->outputLine('The given Resource did not match any method or is unknown.');
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

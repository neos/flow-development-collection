<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 package "FLOW3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

exit ('This script currently only supports the migration of the FLOW3 distribution itself.');

require (__DIR__ . '/../Classes/Utility/Files.php');

define('FLOW3_SAPITYPE', (PHP_SAPI === 'cli' ? 'CLI' : 'Web'));
define('FLOW3_PATH_FLOW3', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../') . '/'))));
define('FLOW3_PATH_ROOT', realpath(FLOW3_PATH_FLOW3 . '/../../../') . '/');
define('FLOW3_PATH_PACKAGES', FLOW3_PATH_ROOT . 'Packages/');
define('FLOW3_PATH_CONFIGURATION', FLOW3_PATH_ROOT . 'Configuration/');

$classNameReplacementMap = array(
	'F3\FLOW3\AOP\Exception\CircularPointcutReference' => 'F3\FLOW3\AOP\Exception\CircularPointcutReferenceException',
	'F3\FLOW3\AOP\Exception\InvalidArgument' => 'F3\FLOW3\AOP\Exception\InvalidArgumentException',
	'F3\FLOW3\AOP\Exception\InvalidConstructorSignature' => 'F3\FLOW3\AOP\Exception\InvalidConstructorSignatureException',
	'F3\FLOW3\AOP\Exception\InvalidPointcutExpression' => 'F3\FLOW3\AOP\Exception\InvalidPointcutExpressionException',
	'F3\FLOW3\AOP\Exception\InvalidTargetClass' => 'F3\FLOW3\AOP\Exception\InvalidTargetClassException',
	'F3\FLOW3\AOP\Exception\UnknowClass' => 'F3\FLOW3\AOP\Exception\UnknowClassException',
	'F3\FLOW3\AOP\Exception\UnknownPointcut' => 'F3\FLOW3\AOP\Exception\UnknownPointcutException',
	
	'F3\FLOW3\Cache\Controller\ManagerController' => 'F3\FLOW3\Cache\Controller\CacheManagerController',
	'F3\FLOW3\Cache\Exception\ClassAlreadyLoaded' => 'F3\FLOW3\Cache\Exception\ClassAlreadyLoadedException',
	'F3\FLOW3\Cache\Exception\DuplicateIdentifier' => 'F3\FLOW3\Cache\Exception\DuplicateIdentifierException',
	'F3\FLOW3\Cache\Exception\InvalidBackend' => 'F3\FLOW3\Cache\Exception\InvalidBackendException',
	'F3\FLOW3\Cache\Exception\InvalidCache' => 'F3\FLOW3\Cache\Exception\InvalidCacheException',
	'F3\FLOW3\Cache\Exception\InvalidData' => 'F3\FLOW3\Cache\Exception\InvalidDataException',
	'F3\FLOW3\Cache\Exception\NoSuchCache' => 'F3\FLOW3\Cache\Exception\NoSuchCacheException',
	'F3\FLOW3\Cache\Factory' => 'F3\FLOW3\Cache\CacheFactory',
	'F3\FLOW3\Cache\Manager' => 'F3\FLOW3\Cache\CacheManager',
	
	'F3\FLOW3\Configuration\Exception\ContainerIsLocked' => 'F3\FLOW3\Configuration\Exception\ContainerIsLockedException',
	'F3\FLOW3\Configuration\Exception\InvalidConfigurationType' => 'F3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException',
	'F3\FLOW3\Configuration\Exception\NoSuchFile' => 'F3\FLOW3\Configuration\Exception\NoSuchFileException',
	'F3\FLOW3\Configuration\Exception\NoSuchOption' => 'F3\FLOW3\Configuration\Exception\NoSuchOptionException',
	'F3\FLOW3\Configuration\Exception\ParseError' => 'F3\FLOW3\Configuration\Exception\ParseErrorException',
	'F3\FLOW3\Configuration\Source\YAMLSource' => 'F3\FLOW3\Configuration\Source\YamlSource',
	'F3\FLOW3\Configuration\Manager' => 'F3\FLOW3\Configuration\ConfigurationManager',

	'F3\FLOW3\Locale\Exception\InvalidLocaleIdentifier' => 'F3\FLOW3\Locale\Exception\InvalidLocaleIdentifierException',
	'F3\FLOW3\Log\Exception\CouldNotOpenResource' => 'F3\FLOW3\Log\Exception\CouldNotOpenResourceException',
	'F3\FLOW3\Log\Exception\InvalidBackend' => 'F3\FLOW3\Log\Exception\InvalidBackendException',
	'F3\FLOW3\Log\Exception\NoSuchBackend' => 'F3\FLOW3\Log\Exception\NoSuchBackendException',
	'F3\FLOW3\MVC\Controller\ControllerContext' => 'F3\FLOW3\MVC\Controller\Context',
	'F3\FLOW3\MVC\Controller\ControllerInterface' => 'F3\FLOW3\MVC\Controller\ControllerInterface',
	'F3\FLOW3\MVC\Controller\Exception\InactivePackage' => 'F3\FLOW3\MVC\Controller\Exception\InactivePackageException',
	'F3\FLOW3\MVC\Controller\Exception\InvalidController' => 'F3\FLOW3\MVC\Controller\Exception\InvalidControllerException',
	'F3\FLOW3\MVC\Controller\Exception\InvalidPackage' => 'F3\FLOW3\MVC\Controller\Exception\InvalidPackageException',
	'F3\FLOW3\MVC\Exception\InfiniteLoop' => 'F3\FLOW3\MVC\Exception\InfiniteLoopException',
	'F3\FLOW3\MVC\Exception\InvalidActionName' => 'F3\FLOW3\MVC\Exception\InvalidActionNameException',
	'F3\FLOW3\MVC\Exception\InvalidArgumentName' => 'F3\FLOW3\MVC\Exception\InvalidArgumentNameException',
	'F3\FLOW3\MVC\Exception\InvalidArgumentType' => 'F3\FLOW3\MVC\Exception\InvalidArgumentTypeException',
	'F3\FLOW3\MVC\Exception\InvalidArgumentValue' => 'F3\FLOW3\MVC\Exception\InvalidArgumentValueException',
	'F3\FLOW3\MVC\Exception\InvalidController' => 'F3\FLOW3\MVC\Exception\InvalidControllerException',
	'F3\FLOW3\MVC\Exception\InvalidControllerName' => 'F3\FLOW3\MVC\Exception\InvalidControllerNameException',
	'F3\FLOW3\MVC\Exception\InvalidFormat' => 'F3\FLOW3\MVC\Exception\InvalidFormatException',
	'F3\FLOW3\MVC\Exception\InvalidMarker' => 'F3\FLOW3\MVC\Exception\InvalidMarkerException',
	'F3\FLOW3\MVC\Exception\InvalidOrMissingRequestHash' => 'F3\FLOW3\MVC\Exception\InvalidOrMissingRequestHashException',
	'F3\FLOW3\MVC\Exception\InvalidPackageKey' => 'F3\FLOW3\MVC\Exception\InvalidPackageKeyException',
	'F3\FLOW3\MVC\Exception\InvalidPart' => 'F3\FLOW3\MVC\Exception\InvalidPartException',
	'F3\FLOW3\MVC\Exception\InvalidRequestMethod' => 'F3\FLOW3\MVC\Exception\InvalidRequestMethodException',
	'F3\FLOW3\MVC\Exception\InvalidRequestType' => 'F3\FLOW3\MVC\Exception\InvalidRequestTypeException',
	'F3\FLOW3\MVC\Exception\InvalidRoutePartHandler' => 'F3\FLOW3\MVC\Exception\InvalidRoutePartHandlerException',
	'F3\FLOW3\MVC\Exception\InvalidTemplateResource' => 'F3\FLOW3\MVC\Exception\InvalidTemplateResourceException',
	'F3\FLOW3\MVC\Exception\InvalidUriPattern' => 'F3\FLOW3\MVC\Exception\InvalidUriPatternException',
	'F3\FLOW3\MVC\Exception\NoSuchAction' => 'F3\FLOW3\MVC\Exception\NoSuchActionException',
	'F3\FLOW3\MVC\Exception\NoSuchArgument' => 'F3\FLOW3\MVC\Exception\NoSuchArgumentException',
	'F3\FLOW3\MVC\Exception\NoSuchController' => 'F3\FLOW3\MVC\Exception\NoSuchControllerException',
	'F3\FLOW3\MVC\Exception\StopAction' => 'F3\FLOW3\MVC\Exception\StopActionException',
	'F3\FLOW3\MVC\Exception\UnsupportedRequestType' => 'F3\FLOW3\MVC\Exception\UnsupportedRequestTypeException',

	'F3\FLOW3\Object\Builder' => 'F3\FLOW3\Object\ObjectBuilder',
	'F3\FLOW3\Object\Exception\CannotBuildObject' => 'F3\FLOW3\Object\Exception\CannotBuildObjectException',
	'F3\FLOW3\Object\Exception\CannotReconstituteObject' => 'F3\FLOW3\Object\Exception\CannotReconstituteObjectException',
	'F3\FLOW3\Object\Exception\InvalidClass' => 'F3\FLOW3\Object\Exception\InvalidClassException',
	'F3\FLOW3\Object\Exception\InvalidObject' => 'F3\FLOW3\Object\Exception\InvalidObjectException',
	'F3\FLOW3\Object\Exception\InvalidObjectConfiguration' => 'F3\FLOW3\Object\Exception\InvalidObjectConfigurationException',
	'F3\FLOW3\Object\Exception\InvalidObjectName' => 'F3\FLOW3\Object\Exception\InvalidObjectNameException',
	'F3\FLOW3\Object\Exception\ObjectAlreadyRegistered' => 'F3\FLOW3\Object\Exception\ObjectAlreadyRegisteredException',
	'F3\FLOW3\Object\Exception\UnknownClass' => 'F3\FLOW3\Object\Exception\UnknownClassException',
	'F3\FLOW3\Object\Exception\UnknownInterface' => 'F3\FLOW3\Object\Exception\UnknownInterfaceException',
	'F3\FLOW3\Object\Exception\UnknownObject' => 'F3\FLOW3\Object\Exception\UnknownObjectException',
	'F3\FLOW3\Object\Exception\UnresolvedDependencies' => 'F3\FLOW3\Object\Exception\UnresolvedDependenciesException',
	'F3\FLOW3\Object\Exception\WrongScope' => 'F3\FLOW3\Object\Exception\WrongScopeException',
	'F3\FLOW3\Object\Factory' => 'F3\FLOW3\Object\ObjectFactory',
	'F3\FLOW3\Object\FactoryInterface' => 'F3\FLOW3\Object\ObjectFactoryInterface',
	'F3\FLOW3\Object\Manager' => 'F3\FLOW3\Object\ObjectManager',
	'F3\FLOW3\Object\ManagerInterface' => 'F3\FLOW3\Object\ObjectManagerInterface',
	'F3\FLOW3\Object\RegistryInterface' => 'F3\FLOW3\Object\RegistryInterface',
	'F3\FLOW3\Object\SessionRegistry' => 'F3\FLOW3\Object\SessionRegistry',
	'F3\FLOW3\Object\TransientRegistry' => 'F3\FLOW3\Object\TransientRegistry',

	'F3\FLOW3\Package\Controller\ManagerController' => 'F3\FLOW3\Package\Controller\PackageManagerController',
	'F3\FLOW3\Package\Exception\DuplicatePackage' => 'F3\FLOW3\Package\Exception\DuplicatePackageException',
	'F3\FLOW3\Package\Exception\InvalidPackageKey' => 'F3\FLOW3\Package\Exception\InvalidPackageKeyException',
	'F3\FLOW3\Package\Exception\InvalidPackagePath' => 'F3\FLOW3\Package\Exception\InvalidPackagePathException',
	'F3\FLOW3\Package\Exception\InvalidPackageState' => 'F3\FLOW3\Package\Exception\InvalidPackageStateException',
	'F3\FLOW3\Package\Exception\PackageKeyAlreadyExists' => 'F3\FLOW3\Package\Exception\PackageKeyAlreadyExistsException',
	'F3\FLOW3\Package\Exception\ProtectedPackageKey' => 'F3\FLOW3\Package\Exception\ProtectedPackageKeyException',
	'F3\FLOW3\Package\Exception\UnknownPackage' => 'F3\FLOW3\Package\Exception\UnknownPackageException',
	'F3\FLOW3\Package\Manager' => 'F3\FLOW3\Package\PackageManager',
	'F3\FLOW3\Package\ManagerInterface' => 'F3\FLOW3\Package\PackageManagerInterface',

	'F3\FLOW3\Persistence\Aspect\DirtyMonitoring' => 'F3\FLOW3\Persistence\Aspect\DirtyMonitoringAspect',
	'F3\FLOW3\Persistence\Exception\IllegalObjectType' => 'F3\FLOW3\Persistence\Exception\IllegalObjectTypeException',
	'F3\FLOW3\Persistence\Exception\MissingBackend' => 'F3\FLOW3\Persistence\Exception\MissingBackendException',
	'F3\FLOW3\Persistence\Exception\TooDirty' => 'F3\FLOW3\Persistence\Exception\TooDirtyException',
	'F3\FLOW3\Persistence\Exception\UnexpectedType' => 'F3\FLOW3\Persistence\Exception\UnexpectedTypeException',
	'F3\FLOW3\Persistence\Exception\UnknownObject' => 'F3\FLOW3\Persistence\Exception\UnknownObjectException',
	'F3\FLOW3\Persistence\Manager' => 'F3\FLOW3\Persistence\PersistenceManager',
	'F3\FLOW3\Persistence\ManagerInterface' => 'F3\FLOW3\Persistence\PersistenceManagerInterface',

	'F3\FLOW3\Property\Exception\FormatNotSupported' => 'F3\FLOW3\Property\Exception\FormatNotSupportedException',
	'F3\FLOW3\Property\Exception\InvalidDataType' => 'F3\FLOW3\Property\Exception\InvalidDataTypeException',
	'F3\FLOW3\Property\Exception\InvalidFormat' => 'F3\FLOW3\Property\Exception\InvalidFormatException',
	'F3\FLOW3\Property\Exception\InvalidProperty' => 'F3\FLOW3\Property\Exception\InvalidPropertyException',
	'F3\FLOW3\Property\Exception\InvalidSource' => 'F3\FLOW3\Property\Exception\InvalidSourceException',
	'F3\FLOW3\Property\Exception\InvalidTarget' => 'F3\FLOW3\Property\Exception\InvalidTargetException',
	'F3\FLOW3\Property\Exception\TargetNotFound' => 'F3\FLOW3\Property\Exception\TargetNotFoundException',

	'F3\FLOW3\Reflection\Exception\InvalidPropertyType' => 'F3\FLOW3\Reflection\Exception\InvalidPropertyTypeException',
	'F3\FLOW3\Reflection\Exception\UnknownClass' => 'F3\FLOW3\Reflection\Exception\UnknownClassException',
	'F3\FLOW3\Reflection\Service' => 'F3\FLOW3\Reflection\ReflectionService',

	'F3\FLOW3\Security\Authentication\ManagerInterface' => 'F3\FLOW3\Security\Authentication\AuthenticationManagerInterface',
	'F3\FLOW3\Security\Authentication\Provider\UsernamePasswordCR' => 'F3\FLOW3\Security\Authentication\Provider\PersistedUsernamePasswordProvider',
	'F3\FLOW3\Security\Authentication\ProviderInterface' => 'F3\FLOW3\Security\Authentication\AuthenticationProviderInterface',
	'F3\FLOW3\Security\Authentication\ProviderManager' => 'F3\FLOW3\Security\Authentication\AuthenticationProviderManager',
	'F3\FLOW3\Security\Authentication\ProviderResolver' => 'F3\FLOW3\Security\Authentication\AuthenticationProviderResolver',
	'F3\FLOW3\Security\Authorization\Voter\ACL' => 'F3\FLOW3\Security\Authorization\Voter\AclVoter',
	'F3\FLOW3\Security\Channel\HTTPSInterceptor' => 'F3\FLOW3\Security\Channel\HttpsInterceptor',
	'F3\FLOW3\Security\Channel\RequestHashService' => 'F3\FLOW3\Security\Channel\RequestHashService',
	'F3\FLOW3\Security\Cryptography\OpenSSLRSAKey' => 'F3\FLOW3\Security\Cryptography\OpenSslRsaKey',
	'F3\FLOW3\Security\Cryptography\RSAWalletServiceInterface' => 'F3\FLOW3\Security\Cryptography\RsaWalletServiceInterface',
	'F3\FLOW3\Security\Cryptography\RSAWalletServicePHP' => 'F3\FLOW3\Security\Cryptography\RsaWalletServicePhp',
	'F3\FLOW3\Security\Exception\AccessDenied' => 'F3\FLOW3\Security\Exception\AccessDeniedException',
	'F3\FLOW3\Security\Exception\AuthenticationRequired' => 'F3\FLOW3\Security\Exception\AuthenticationRequiredException',
	'F3\FLOW3\Security\Exception\CircularResourceDefinitionDetected' => 'F3\FLOW3\Security\Exception\CircularResourceDefinitionDetectedException',
	'F3\FLOW3\Security\Exception\DecryptionNotAllowed' => 'F3\FLOW3\Security\Exception\DecryptionNotAllowedException',
	'F3\FLOW3\Security\Exception\InvalidArgumentForHashGeneration' => 'F3\FLOW3\Security\Exception\InvalidArgumentForHashGenerationException',
	'F3\FLOW3\Security\Exception\InvalidArgumentForRequestHashGeneration' => 'F3\FLOW3\Security\Exception\InvalidArgumentForRequestHashGenerationException',
	'F3\FLOW3\Security\Exception\InvalidAuthenticationProvider' => 'F3\FLOW3\Security\Exception\InvalidAuthenticationProviderException',
	'F3\FLOW3\Security\Exception\InvalidAuthenticationStatus' => 'F3\FLOW3\Security\Exception\InvalidAuthenticationStatusException',
	'F3\FLOW3\Security\Exception\InvalidKeyPairID' => 'F3\FLOW3\Security\Exception\InvalidKeyPairIdException',
	'F3\FLOW3\Security\Exception\MissingConfiguration' => 'F3\FLOW3\Security\Exception\MissingConfigurationException',
	'F3\FLOW3\Security\Exception\NoAuthenticationProviderFound' => 'F3\FLOW3\Security\Exception\NoAuthenticationProviderFoundException',
	'F3\FLOW3\Security\Exception\NoContextAvailable' => 'F3\FLOW3\Security\Exception\NoContextAvailableException',
	'F3\FLOW3\Security\Exception\NoEntryInPolicy' => 'F3\FLOW3\Security\Exception\NoEntryInPolicyException',
	'F3\FLOW3\Security\Exception\NoEntryPointFound' => 'F3\FLOW3\Security\Exception\NoEntryPointFoundException',
	'F3\FLOW3\Security\Exception\NoInterceptorFound' => 'F3\FLOW3\Security\Exception\NoInterceptorFoundException',
	'F3\FLOW3\Security\Exception\NoRequestPatternFound' => 'F3\FLOW3\Security\Exception\NoRequestPatternFoundException',
	'F3\FLOW3\Security\Exception\OperationNotPermitted' => 'F3\FLOW3\Security\Exception\OperationNotPermittedException',
	'F3\FLOW3\Security\Exception\RequestTypeNotSupported' => 'F3\FLOW3\Security\Exception\RequestTypeNotSupportedException',
	'F3\FLOW3\Security\Exception\SyntacticallyWrongRequestHash' => 'F3\FLOW3\Security\Exception\SyntacticallyWrongRequestHashException',
	'F3\FLOW3\Security\Exception\UnsupportedAuthenticationToken' => 'F3\FLOW3\Security\Exception\UnsupportedAuthenticationTokenException',
	'F3\FLOW3\Security\Exception\VoterNotFound' => 'F3\FLOW3\Security\Exception\VoterNotFoundException',

	'F3\FLOW3\Session\Exception\DataNotSerializeable' => 'F3\FLOW3\Session\Exception\DataNotSerializeableException',
	'F3\FLOW3\Session\Exception\SessionAutostartIsEnabled' => 'F3\FLOW3\Session\Exception\SessionAutostartIsEnabledException',
	'F3\FLOW3\Session\Exception\SessionNotStarted' => 'F3\FLOW3\Session\Exception\SessionNotStartedException',
	'F3\FLOW3\Session\PHPSession' => 'F3\FLOW3\Session\PhpSession',

	'F3\FLOW3\SignalSlot\Exception\InvalidSlot' => 'F3\FLOW3\SignalSlot\Exception\InvalidSlotException',

	'F3\FLOW3\Validation\Exception\InvalidSubject' => 'F3\FLOW3\Validation\Exception\InvalidSubjectException',
	'F3\FLOW3\Validation\Exception\InvalidValidationConfiguration' => 'F3\FLOW3\Validation\Exception\InvalidValidationConfigurationException',
	'F3\FLOW3\Validation\Exception\InvalidValidationOptions' => 'F3\FLOW3\Validation\Exception\InvalidValidationOptionsException',
	'F3\FLOW3\Validation\Exception\NoSuchFilter' => 'F3\FLOW3\Validation\Exception\NoSuchFilterException',
	'F3\FLOW3\Validation\Exception\NoSuchValidator' => 'F3\FLOW3\Validation\Exception\NoSuchValidatorException',
	'F3\FLOW3\Validation\Validator\UUIDValidator' => 'F3\FLOW3\Validation\Validator\UuidValidator',
	'F3\FLOW3\Private\AOP\AOPProxyClassTemplate' => 'F3\FLOW3\Private\AOP\AopProxyClassTemplate',

	'F3\Fluid\View\Exception\InvalidTemplateResource' => 'F3\Fluid\View\Exception\InvalidTemplateResourceException',

	'F3\Testing\Controller\CLIController' => 'F3\Testing\Controller\CliController',
	'F3\TYPO3CR\Storage\Backend\PDO' => 'F3\TYPO3CR\Storage\Backend\Pdo',
	'F3\TYPO3CR\Storage\Search\PDO' => 'F3\TYPO3CR\Storage\Search\Pdo',

	'F3\YAML\YAML' => 'F3\YAML\Yaml',
);



$phpFiles = \F3\FLOW3\Utility\Files::readDirectoryRecursively(__DIR__ . '/../../../../', '.php', TRUE);
$yamlFiles = \F3\FLOW3\Utility\Files::readDirectoryRecursively(__DIR__ . '/../../../../', '.yaml', TRUE);
$xmlFiles = \F3\FLOW3\Utility\Files::readDirectoryRecursively(__DIR__ . '/../../../../', '.xml', TRUE);

$allPathsAndFilenames = array_merge($phpFiles, $yamlFiles, $xmlFiles);
unset($allPathsAndFilenames[(array_search(realpath(__FILE__), $allPathsAndFilenames))]);

foreach ($allPathsAndFilenames as $pathAndFilename) {
	echo '> ' . $pathAndFilename . chr(10);
	$pathInfo = pathinfo($pathAndFilename);
	if (!isset($pathInfo['filename'])) continue;

	$pathSegments = explode('/', (substr($pathAndFilename, strlen(FLOW3_PATH_PACKAGES))));
	if (isset($pathSegments[2]) && $pathSegments[2] === 'Resources') continue;

	$file = file_get_contents($pathAndFilename);
	$fileBackup = $file;
	$newPathAndFilename = $pathAndFilename;

	foreach ($classNameReplacementMap as $oldClassName => $newClassName) {
		$file = preg_replace('/([^a-zA-Z])' . str_replace('\\', '\\\\', $oldClassName) . '([^a-zA-Z])/', '$1' . $newClassName . '$2', $file);
	}
	
	if ($pathInfo['extension'] == 'php') {
		if (count($pathSegments) > 1) {
			list(, $packageKey) = $pathSegments;
			
			if ($pathSegments[2] == 'Classes') {
				$oldFullyQualifiedClassName = substr('F3\\' . $packageKey. '\\' . implode('\\', array_slice($pathSegments, 3)), 0, -4);
				if (isset($classNameReplacementMap[$oldFullyQualifiedClassName])) {
					$oldClassName = implode('', array_slice(explode('\\', $oldFullyQualifiedClassName), -1, 1));
					$newClassName = implode('', array_slice(explode('\\', $classNameReplacementMap[$oldFullyQualifiedClassName]), -1, 1));
					$file = preg_replace('/([class|interface]) ' . $oldClassName . ' /', '$1 ' . $newClassName . ' ', $file);
					$newPathAndFilename = $pathInfo['dirname'] . '/' . $newClassName . '.php';
				}
			} elseif ($pathSegments[2] == 'Tests') {
				$oldFullyQualifiedTestcaseClassName = substr('F3\\' . $packageKey. '\\' . implode('\\', array_slice($pathSegments, 4)), 0, -4);
				$fullyQualifiedTestSubjectClassName = substr($oldFullyQualifiedTestcaseClassName, 0, -4);
				if (isset($classNameReplacementMap[$fullyQualifiedTestSubjectClassName])) {
					$oldTestcaseClassName = implode('', array_slice(explode('\\', $oldFullyQualifiedTestcaseClassName), -1, 1));
					$newTestcaseClassName = implode('', array_slice(explode('\\', $classNameReplacementMap[$fullyQualifiedTestSubjectClassName] . 'Test'), -1, 1));
					$file = preg_replace('/(class )' . $oldTestcaseClassName . ' /', 'class ' . $newTestcaseClassName . ' ', $file);
					$newPathAndFilename = $pathInfo['dirname'] . '/' . $newTestcaseClassName . '.php';
				}
			}
		}
	}
	
	if ($file !== $fileBackup) {
		file_put_contents($pathAndFilename, $file);
		if ($newPathAndFilename !== $pathAndFilename) {
			system('svn mv ' . escapeshellarg($pathAndFilename) . ' ' . escapeshellarg($newPathAndFilename) . chr(10));
		}
	}

	unset($file);
	unset($fileBackup);
}

?>
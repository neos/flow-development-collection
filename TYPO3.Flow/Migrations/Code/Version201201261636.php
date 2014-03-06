<?php
namespace TYPO3\Flow\Core\Migrations;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Adjust to the major changes in FLOW3 1.1 when coming from 1.0.
 */
class Version201201261636 extends AbstractMigration {

	/**
	 * Returns the identifier of this migration.
	 *
	 * Hardcoded to be stable after the rename to TYPO3 Flow.
	 *
	 * @return string
	 */
	public function getIdentifier() {
		return 'TYPO3.FLOW3-201201261636';
	}

	/**
	 * @return void
	 */
	public function up() {
		$this->searchAndReplace('TYPO3\FLOW3\MVC\CLI', 'TYPO3\FLOW3\Cli');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Web\Routing', 'TYPO3\FLOW3\Mvc\Routing');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Web\Request', 'TYPO3\FLOW3\Mvc\ActionRequest');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Web\Response', 'TYPO3\FLOW3\Http\Response');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Web\SubRequest', 'TYPO3\FLOW3\Mvc\ActionRequest');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Web\SubResponse', 'TYPO3\FLOW3\Http\Response');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\Controller\CommandController', 'TYPO3\FLOW3\Cli\CommandController');
		$this->searchAndReplace('TYPO3\FLOW3\Property\DataType\Uri', 'TYPO3\FLOW3\Http\Uri');
		$this->searchAndReplace('TYPO3\FLOW3\AOP', 'TYPO3\FLOW3\Aop');
		$this->searchAndReplace('TYPO3\FLOW3\MVC', 'TYPO3\FLOW3\Mvc');
		$this->searchAndReplace('TYPO3\FLOW3\MVC\RequestInterface', 'TYPO3\FLOW3\Http\Request');
		$this->searchAndReplace('\AOP', '\Aop');
		$this->searchAndReplace('\MVC', '\Mvc');

		$this->searchAndReplace('->getRootRequest()', '->getMainRequest()');
		$this->searchAndReplace('$this->controllerContext->getRequest()->getBaseUri()', '$this->controllerContext->getRequest()->getHttpRequest()->getBaseUri()');
		$this->searchAndReplace('->getOriginalRequestMappingResults()', '->getInternalArgument(\'__submittedArgumentValidationResults\')');
		$this->searchAndReplace('->getOriginalRequest()->getArguments()', '->getInternalArgument(\'__submittedArguments\')');

		$this->showNote('\TYPO3\FLOW3\MVC\Web\RequestBuilder does not exist anymore. If you need to create requests, do "new ActionRequest($parentRequest)".');
		$this->showNote('\TYPO3\FLOW3\MVC\Web\SubRequestBuilder does not exist anymore. If you need to create sub requests, do "new ActionRequest($parentRequest)".');
		$this->showNote('\TYPO3\FLOW3\MVC\RequestInterface has been removed, use \TYPO3\FLOW3\Mvc\ActionRequest instead - e.g. if you implemented your own token.');
		$this->showNote('Handling of NULL values in the database has changed, add "@ORM\Column(nullable=true)" to properties that need to be nullable or give them a non-NULL default.');
		$this->showNote('All persistence repositories must be of scope "singleton", this is now enforced. Add "@FLOW3\Scope("singleton")" if not already present.');
		$this->showNote('$supportedRequestTypes are not needed anymore in a controller.');
		$this->showNote('Validators now accept empty values by default, if you need the previous behavior, add "@FLOW3\Validate(type="NotEmpty")" where needed.');
		$this->showNote('Settings.yaml: The previously shipped "DefaultProvider" authentication provider configuration has been removed, you must configure all providers yourself now.');
		$this->showNote('Settings.yaml: "providerClass" is deprecated, use "provider" instead. Provider options are now given in "providerOptions".');
		$this->showNote('Settings.yaml: Authentication "entryPoint" configuration needs to be changed from:
 entryPoint:
   WebRedirect:
     uri: login.html
to:
 entryPoint: \'WebRedirect\'
 entryPointOptions:
   uri: \'login.html\'');
		$this->showNote('Routes.yaml: Widget configuration needs to be adjusted to refer to the correct widget id, see upgrading instructions.');

		$this->showWarning('Class names in pointcut expressions might not be fully qualified, check manually whether (more) adjustments are needed.');
	}

}

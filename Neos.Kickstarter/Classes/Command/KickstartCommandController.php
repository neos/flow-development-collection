<?php
namespace Neos\Kickstarter\Command;

/*
 * This file is part of the Neos.Kickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Utility\Arrays;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Kickstarter\Utility\Validation;

/**
 * Command controller for the Kickstart generator
 *
 */
class KickstartCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var \Neos\Flow\Package\PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @Flow\Inject
     * @var \Neos\Kickstarter\Service\GeneratorService
     */
    protected $generatorService;

    /**
     * Kickstart a new package
     *
     * Creates a new package and creates a standard Action Controller and a sample
     * template for its Index Action.
     *
     * For creating a new package without sample code use the package:create command.
     *
     * @param string $packageKey The package key, for example "MyCompany.MyPackageName"
     * @return string
     * @see typo3.flow:package:create
     */
    public function packageCommand($packageKey)
    {
        $this->validatePackageKey($packageKey);

        if ($this->packageManager->isPackageAvailable($packageKey)) {
            $this->outputLine('Package "%s" already exists.', array($packageKey));
            exit(2);
        }
        $this->packageManager->createPackage($packageKey);
        $this->actionControllerCommand($packageKey, 'Standard');
        $this->documentationCommand($packageKey);
    }

    /**
     * Kickstart a new action controller
     *
     * Generates an Action Controller with the given name in the specified package.
     * In its default mode it will create just the controller containing a sample
     * indexAction.
     *
     * By specifying the --generate-actions flag, this command will also create a
     * set of actions. If no model or repository exists which matches the
     * controller name (for example "CoffeeRepository" for "CoffeeController"),
     * an error will be shown.
     *
     * Likewise the command exits with an error if the specified package does not
     * exist. By using the --generate-related flag, a missing package, model or
     * repository can be created alongside, avoiding such an error.
     *
     * By specifying the --generate-templates flag, this command will also create
     * matching Fluid templates for the actions created. This option can only be
     * used in combination with --generate-actions.
     *
     * The default behavior is to not overwrite any existing code. This can be
     * overridden by specifying the --force flag.
     *
     * @param string $packageKey The package key of the package for the new controller with an optional subpackage, (e.g. "MyCompany.MyPackage/Admin").
     * @param string $controllerName The name for the new controller. This may also be a comma separated list of controller names.
     * @param boolean $generateActions Also generate index, show, new, create, edit, update and delete actions.
     * @param boolean $generateTemplates Also generate the templates for each action.
     * @param boolean $generateRelated Also create the mentioned package, related model and repository if neccessary.
     * @param boolean $force Overwrite any existing controller or template code. Regardless of this flag, the package, model and repository will never be overwritten.
     * @return string
     * @see neos.kickstarter:kickstart:commandcontroller
     */
    public function actionControllerCommand($packageKey, $controllerName, $generateActions = false, $generateTemplates = true, $generateRelated = false, $force = false)
    {
        $subpackageName = '';
        if (strpos($packageKey, '/') !== false) {
            list($packageKey, $subpackageName) = explode('/', $packageKey, 2);
        }
        $this->validatePackageKey($packageKey);
        if (!$this->packageManager->isPackageAvailable($packageKey)) {
            if ($generateRelated === false) {
                $this->outputLine('Package "%s" is not available.', array($packageKey));
                $this->outputLine('Hint: Use --generate-related for creating it!');
                exit(2);
            }
            $this->packageManager->createPackage($packageKey);
        }
        $generatedFiles = array();
        $generatedModels = false;

        $controllerNames = Arrays::trimExplode(',', $controllerName);
        if ($generateActions === true) {
            foreach ($controllerNames as $currentControllerName) {
                $modelClassName = str_replace('.', '\\', $packageKey) . '\Domain\Model\\' . $currentControllerName;
                if (!class_exists($modelClassName)) {
                    if ($generateRelated === true) {
                        $generatedFiles += $this->generatorService->generateModel($packageKey, $currentControllerName, array('name' => array('type' => 'string')));
                        $generatedModels = true;
                    } else {
                        $this->outputLine('The model %s does not exist, but is necessary for creating the respective actions.', array($modelClassName));
                        $this->outputLine('Hint: Use --generate-related for creating it!');
                        exit(3);
                    }
                }

                $repositoryClassName = str_replace('.', '\\', $packageKey) . '\Domain\Repository\\' . $currentControllerName . 'Repository';
                if (!class_exists($repositoryClassName)) {
                    if ($generateRelated === true) {
                        $generatedFiles += $this->generatorService->generateRepository($packageKey, $currentControllerName);
                    } else {
                        $this->outputLine('The repository %s does not exist, but is necessary for creating the respective actions.', array($repositoryClassName));
                        $this->outputLine('Hint: Use --generate-related for creating it!');
                        exit(4);
                    }
                }
            }
        }

        foreach ($controllerNames as $currentControllerName) {
            if ($generateActions === true) {
                $generatedFiles += $this->generatorService->generateCrudController($packageKey, $subpackageName, $currentControllerName, $force);
            } else {
                $generatedFiles += $this->generatorService->generateActionController($packageKey, $subpackageName, $currentControllerName, $force);
            }
            if ($generateTemplates === true) {
                $generatedFiles += $this->generatorService->generateLayout($packageKey, 'Default', $force);
                if ($generateActions === true) {
                    $generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Index', 'Index', $force);
                    $generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'New', 'New', $force);
                    $generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Edit', 'Edit', $force);
                    $generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Show', 'Show', $force);
                } else {
                    $generatedFiles += $this->generatorService->generateView($packageKey, $subpackageName, $currentControllerName, 'Index', 'SampleIndex', $force);
                }
            }
        }

        $this->outputLine(implode(PHP_EOL, $generatedFiles));

        if ($generatedModels === true) {
            $this->outputLine('As new models were generated, don\'t forget to update the database schema with the respective doctrine:* commands.');
        }
    }

    /**
     * Kickstart a new command controller
     *
     * Creates a new command controller with the given name in the specified
     * package. The generated controller class already contains an example command.
     *
     * @param string $packageKey The package key of the package for the new controller
     * @param string $controllerName The name for the new controller. This may also be a comma separated list of controller names.
     * @param boolean $force Overwrite any existing controller.
     * @return string
     * @see neos.kickstarter:kickstart:actioncontroller
     */
    public function commandControllerCommand($packageKey, $controllerName, $force = false)
    {
        $this->validatePackageKey($packageKey);
        if (!$this->packageManager->isPackageAvailable($packageKey)) {
            $this->outputLine('Package "%s" is not available.', array($packageKey));
            exit(2);
        }
        $generatedFiles = array();
        $controllerNames = Arrays::trimExplode(',', $controllerName);
        foreach ($controllerNames as $currentControllerName) {
            $generatedFiles += $this->generatorService->generateCommandController($packageKey, $currentControllerName, $force);
        }
        $this->outputLine(implode(PHP_EOL, $generatedFiles));
    }

    /**
     * Kickstart a new domain model
     *
     * This command generates a new domain model class. The fields are specified as
     * a variable list of arguments with field name and type separated by a colon
     * (for example "title:string" "size:int" "type:MyType").
     *
     * @param string $packageKey The package key of the package for the domain model
     * @param string $modelName The name of the new domain model class
     * @param boolean $force Overwrite any existing model.
     * @return string
     * @see neos.kickstarter:kickstart:repository
     */
    public function modelCommand($packageKey, $modelName, $force = false)
    {
        $this->validatePackageKey($packageKey);
        if (!$this->packageManager->isPackageAvailable($packageKey)) {
            $this->outputLine('Package "%s" is not available.', array($packageKey));
            exit(2);
        }

        $this->validateModelName($modelName);

        $fieldsArguments = $this->request->getExceedingArguments();
        $fieldDefinitions = array();
        foreach ($fieldsArguments as $fieldArgument) {
            list($fieldName, $fieldType) = explode(':', $fieldArgument, 2);

            $fieldDefinitions[$fieldName] = array('type' => $fieldType);
            if (strpos($fieldType, 'array') !== false) {
                $fieldDefinitions[$fieldName]['typeHint'] = 'array';
            } elseif (strpos($fieldType, '\\') !== false) {
                if (strpos($fieldType, '<') !== false) {
                    $fieldDefinitions[$fieldName]['typeHint'] = substr($fieldType, 0, strpos($fieldType, '<'));
                } else {
                    $fieldDefinitions[$fieldName]['typeHint'] = $fieldType;
                }
            }
        };

        $generatedFiles = $this->generatorService->generateModel($packageKey, $modelName, $fieldDefinitions, $force);
        $this->outputLine(implode(PHP_EOL, $generatedFiles));
        $this->outputLine('As a new model was generated, don\'t forget to update the database schema with the respective doctrine:* commands.');
    }

    /**
     * Kickstart a new domain repository
     *
     * This command generates a new domain repository class for the given model name.
     *
     * @param string $packageKey The package key
     * @param string $modelName The name of the domain model class
     * @param boolean $force Overwrite any existing repository.
     * @return string
     * @see neos.kickstarter:kickstart:model
     */
    public function repositoryCommand($packageKey, $modelName, $force = false)
    {
        $this->validatePackageKey($packageKey);
        if (!$this->packageManager->isPackageAvailable($packageKey)) {
            $this->outputLine('Package "%s" is not available.', array($packageKey));
            exit(2);
        }

        $generatedFiles = $this->generatorService->generateRepository($packageKey, $modelName, $force);
        $this->outputLine(implode(PHP_EOL, $generatedFiles));
    }

    /**
     * Kickstart documentation
     *
     * Generates a documentation skeleton for the given package.
     *
     * @param string $packageKey The package key of the package for the documentation
     * @return string
     */
    public function documentationCommand($packageKey)
    {
        $this->validatePackageKey($packageKey);
        if (!$this->packageManager->isPackageAvailable($packageKey)) {
            $this->outputLine('Package "%s" is not available.', array($packageKey));
            exit(2);
        }

        $generatedFiles = $this->generatorService->generateDocumentation($packageKey);

        $this->outputLine(implode(PHP_EOL, $generatedFiles));
    }

    /**
     * Checks the syntax of the given $packageKey and quits with an error message if it's not valid
     *
     * @param string $packageKey
     * @return void
     */
    protected function validatePackageKey($packageKey)
    {
        if (!$this->packageManager->isPackageKeyValid($packageKey)) {
            $this->outputLine('Package key "%s" is not valid. Only UpperCamelCase with alphanumeric characters in the format <VendorName>.<PackageKey>, please!', array($packageKey));
            exit(1);
        }
    }

    /**
     * Check the given model name to be not one of the reserved words of PHP.
     *
     * @param string $modelName
     * @return boolean
     * @see http://www.php.net/manual/en/reserved.keywords.php
     */
    protected function validateModelName($modelName)
    {
        if (Validation::isReservedKeyword($modelName)) {
            $this->outputLine('The name of the model cannot be one of the reserved words of PHP!');
            $this->outputLine('Have a look at: http://www.php.net/manual/en/reserved.keywords.php');
            exit(3);
        }
    }
}

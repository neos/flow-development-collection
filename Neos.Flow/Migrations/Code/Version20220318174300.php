<?php
namespace Neos\Flow\Core\Migrations;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Utility\Arrays;

/**
 * Adjust "Settings.yaml" to remove legacy fluid custom error view options (see https://github.com/neos/flow-development-collection/issues/2742)
 *
 * concerning Neos.Flow.error.exceptionHandler.renderingGroups.{exampleGroup}.options
 *
 * options:
 * -  templatePathAndFilename: 'file'
 * -  layoutRootPath: 'path'
 * -  partialRootPath: 'path'
 * -  format: 'html'
 * +  viewOptions:
 * +    templatePathAndFilename: 'file'
 * +    layoutRootPaths: ['path']
 * +    partialRootPaths: ['path']
 *
 */
class Version20220318174300 extends AbstractMigration
{
    public function getIdentifier(): string
    {
        return 'Neos.Flow-20220318174300';
    }

    public function up(): void
    {
        $this->processConfiguration(
            ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            fn (array &$configuration) => $this->processErrorViewSettings($configuration),
            true
        );
    }

    /**
     * Adjust legacy fluid view options: Neos.Flow.error.exceptionHandler.renderingGroups.{exampleGroup}.options
     * show warning if Neos.Flow.error.exceptionHandler.defaultRenderingOptions was used with legacy fluid view options
     */
    public function processErrorViewSettings(array &$configuration): void
    {
        if (self::arrayValueByPathIsArray($configuration, 'Neos.Flow.error.exceptionHandler.defaultRenderingOptions')) {
            $defaultRenderingOptions = $configuration['Neos']['Flow']['error']['exceptionHandler']['defaultRenderingOptions'];
            if (isset($defaultRenderingOptions['templatePathAndFilename'])
                || isset($defaultRenderingOptions['layoutRootPath'])
                || isset($defaultRenderingOptions['partialRootPath'])
                || isset($defaultRenderingOptions['format'])) {
                // moving these options globally to the 'viewOptions' subkey would lead to that every view (not only fluid) will get them applied and not supported options view errors will occur.
                $this->showWarning("No automatic migration can be done for the global legacy fluid error view options:\nNeos.Flow.error.exceptionHandler.defaultRenderingOptions");
            }
        }

        if (self::arrayValueByPathIsArray($configuration, 'Neos.Flow.error.exceptionHandler.renderingGroups') === false) {
            return;
        }
        $renderingGroups = &$configuration['Neos']['Flow']['error']['exceptionHandler']['renderingGroups'];

        foreach ($renderingGroups as $renderingGroupName => &$renderingGroupConfig) {
            if (self::arrayValueByPathIsArray($renderingGroupConfig, 'options') === false) {
                continue;
            }
            $options = &$renderingGroupConfig['options'];

            // config path for logs:
            $renderingGroupPath = "Neos.Flow.error.exceptionHandler.renderingGroups.$renderingGroupName";

            if (self::arrayValueByPathIsArray($options, 'viewOptions') === false) {
                $options['viewOptions'] = [];
            }
            $viewOptions = &$options['viewOptions'];

            //
            // templatePathAndFilename
            //
            if (isset($options['templatePathAndFilename'])) {
                $value = $options['templatePathAndFilename'];
                unset($options['templatePathAndFilename']);
                if (is_string($value) && $value !== '') {
                    $viewOptions['templatePathAndFilename'] = $value;
                    $this->showNote("Moved 'templatePathAndFilename' to 'viewOptions.templatePathAndFilename' subkey:\n $renderingGroupPath.options.templatePathAndFilename = $value");
                } else {
                    // templatePathAndFilename: true
                    // was a workaround:
                    // https://github.com/neos/flow-development-collection/issues/1108
                    $this->showNote("Old 'templatePathAndFilename' workaround removed:\n  $renderingGroupPath.options.templatePathAndFilename = $value");
                }
            }

            //
            // layoutRootPath
            //
            if (isset($options['layoutRootPath'])) {
                $value = $options['layoutRootPath'];
                unset($options['layoutRootPath']);
                if (self::arrayValueByPathIsArray($viewOptions, 'layoutRootPaths') === false) {
                    $viewOptions['layoutRootPaths'] = [];
                }
                $viewOptions['layoutRootPaths'][] = $value;
                $this->showNote("Moved 'layoutRootPath' to 'viewOptions.layoutRootPaths' subkey:\n $renderingGroupPath.options.layoutRootPath = $value");
            }

            //
            // partialRootPath
            //
            if (isset($options['partialRootPath'])) {
                $value = $options['partialRootPath'];
                unset($options['partialRootPath']);
                if (self::arrayValueByPathIsArray($viewOptions, 'partialRootPaths') === false) {
                    $viewOptions['partialRootPaths'] = [];
                }
                $viewOptions['partialRootPaths'][] = $value;
                $this->showNote("Moved 'layoutRootPath' to 'viewOptions.layoutRootPaths' subkey:\n $renderingGroupPath.options.layoutRootPath = $value");
            }

            //
            // format
            //
            if (isset($options['format'])) {
                $value = $options['format'];
                if ($value === 'html') {
                    unset($options['format']);
                    $this->showNote("Unnecessary format option removed:\n $renderingGroupPath.options.format = $value");
                } else {
                    $this->showWarning("There is no replacement for the legacy fluid option format:\n $renderingGroupPath.options.format = $value");
                }
            }
        }
    }

    /**
     * checks if (isset($array['path']) && is_array($array['path']))
     */
    protected static function arrayValueByPathIsArray(array $array, string $path): bool
    {
        $value = Arrays::getValueByPath($array, $path);
        return is_array($value);
    }
}

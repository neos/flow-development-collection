<?php
namespace TYPO3\Flow\Core\Migrations;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Security\RequestPattern\ControllerObjectName;
use TYPO3\Flow\Security\RequestPattern\CsrfProtection;
use TYPO3\Flow\Security\RequestPattern\Host;
use TYPO3\Flow\Security\RequestPattern\Ip;
use TYPO3\Flow\Security\RequestPattern\Uri;

/**
 * Adjust "Settings.yaml" to new "requestPattern" and "firewall" syntax (see FLOW-412)
 */
class Version20151113161300 extends AbstractMigration
{
    /**
     * @return void
     */
    public function up()
    {
        $this->processConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS,
            function (array &$configuration) {
                $this->processRequestPatterns($configuration);
                $this->processFirewallFilters($configuration);
            },
            true
        );
    }

    /**
     * Adjust TYPO3.Flow.security.authentication.providers.<providerName>.requestPatterns syntax
     *
     * @param array $configuration
     * @return void
     */
    public function processRequestPatterns(array &$configuration)
    {
        if (!isset($configuration['TYPO3']['Flow']['security']['authentication']['providers'])) {
            return;
        }
        foreach ($configuration['TYPO3']['Flow']['security']['authentication']['providers'] as $providerName => &$providerOptions) {
            if (!isset($providerOptions['requestPatterns'])) {
                continue;
            }
            foreach ($providerOptions['requestPatterns'] as $requestPatternName => $requestPatternOptions) {
                // already converted?
                if (isset($requestPatternOptions['pattern'])) {
                    continue;
                }
                switch (strtolower($requestPatternName)) {
                    case 'controllerobjectname':
                        $requestPatternOptions = [
                            'pattern' => 'ControllerObjectName',
                            'patternOptions' => ['controllerObjectNamePattern' => $requestPatternOptions]
                        ];
                        break;
                    case 'csrfprotection':
                        $requestPatternOptions = [
                            'pattern' => 'CsrfProtection',
                        ];
                        break;
                    case 'host':
                        $requestPatternOptions = [
                            'pattern' => 'Host',
                            'patternOptions' => ['hostPattern' => $requestPatternOptions]
                        ];
                        break;
                    case 'ip':
                        $requestPatternOptions = [
                            'pattern' => 'Ip',
                            'patternOptions' => ['cidrPattern' => $requestPatternOptions]
                        ];
                        break;
                    case 'uri':
                        $requestPatternOptions = [
                            'pattern' => 'Uri',
                            'patternOptions' => ['uriPattern' => $requestPatternOptions]
                        ];
                        break;
                    default:
                        $this->showWarning(sprintf('Could not automatically convert the syntax of the custom request pattern "%s". Please adjust it manually as described in the documentation.', $requestPatternName));
                        continue 2;
                }
                $patternIdentifier = $this->targetPackageData['packageKey'] . ':' . $this->getShortClassName($requestPatternName);
                $providerOptions['requestPatterns'][$patternIdentifier] = $requestPatternOptions;
                unset($providerOptions['requestPatterns'][$requestPatternName]);
                $this->showNote(sprintf('Adjusted configuration syntax of the "%s" request pattern.', $requestPatternName));
            }
        }
    }

    /**
     * Adjust TYPO3.Flow.security.authentication.providers.<providerName>.requestPatterns syntax
     *
     * @param array $configuration
     * @return void
     */
    public function processFirewallFilters(array &$configuration)
    {
        if (!isset($configuration['TYPO3']['Flow']['security']['firewall']['filters'])) {
            return;
        }
        $filtersConfiguration = &$configuration['TYPO3']['Flow']['security']['firewall']['filters'];
        foreach ($filtersConfiguration as $filterIndex => &$filterOptions) {
            // already converted?
            if (isset($filterOptions['pattern']) || !isset($filterOptions['patternType'])) {
                continue;
            }
            $patternValue = isset($filterOptions['patternValue']) ? $filterOptions['patternValue'] : '';

            switch (strtolower($filterOptions['patternType'])) {
                case 'controllerobjectname':
                    $patternClassName = ControllerObjectName::class;
                    $filterOptions['pattern'] = 'ControllerObjectName';
                    $filterOptions['patternOptions'] = ['controllerObjectNamePattern' => $patternValue];
                    break;
                case 'csrfprotection':
                    $patternClassName = CsrfProtection::class;
                    $filterOptions['pattern'] = 'CsrfProtection';
                    break;
                case 'host':
                    $patternClassName = Host::class;
                    $filterOptions['pattern'] = 'Host';
                    $filterOptions['patternOptions'] = ['hostPattern' => $patternValue];
                    break;
                case 'ip':
                    $patternClassName = Ip::class;
                    $filterOptions['pattern'] = 'Ip';
                    $filterOptions['patternOptions'] = ['cidrPattern' => $patternValue];
                    break;
                case 'uri':
                    $patternClassName = Uri::class;
                    $filterOptions['pattern'] = 'Uri';
                    $filterOptions['patternOptions'] = ['uriPattern' => $patternValue];
                    break;
                default:
                    $this->showWarning(sprintf('Could not automatically convert the syntax of the custom firewall filter "%s". Please adjust it manually as described in the documentation.', $filterOptions['patternType']));
                    $patternClassName = $filterOptions['patternType'];
            }
            if (isset($filterOptions['pattern'])) {
                unset($filterOptions['patternType'], $filterOptions['patternValue']);
            }
            if (is_numeric($filterIndex)) {
                $patternIdentifier = $this->targetPackageData['packageKey'] . ':' . $this->getShortClassName($patternClassName);
                $uniquePatternIdentifier = $patternIdentifier;
                $loopCounter = 1;
                while (isset($filtersConfiguration[$uniquePatternIdentifier])) {
                    $uniquePatternIdentifier = $patternIdentifier . '_' . (++ $loopCounter);
                }
                unset($filtersConfiguration[$filterIndex]);
                $filtersConfiguration[$uniquePatternIdentifier] = $filterOptions;
            }
            $this->showNote(sprintf('Adjusted configuration syntax of the "%s" firewall filter.', $patternClassName));
        }
    }

    /**
     * @param string $fullyQualifiedClassName
     * @return string
     */
    private function getShortClassName($fullyQualifiedClassName)
    {
        $classNameParts = explode('\\', $fullyQualifiedClassName);
        return array_pop($classNameParts);
    }
}

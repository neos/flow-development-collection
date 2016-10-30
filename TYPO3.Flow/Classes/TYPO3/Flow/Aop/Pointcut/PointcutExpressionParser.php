<?php
namespace TYPO3\Flow\Aop\Pointcut;

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
use TYPO3\Flow\Aop\Builder\ProxyClassBuilder;
use TYPO3\Flow\Aop\Exception\InvalidPointcutExpressionException;
use TYPO3\Flow\Aop\Exception as AopException;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;

/**
 * The pointcut expression parser parses the definition of the place and circumstances
 * where advices can be inserted later on. The input of the parse() function is a string
 * from a pointcut- or advice annotation and returns a pointcut filter composite.
 *
 * @see \TYPO3\Flow\Aop\Pointcut, PointcutFilterComposite
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class PointcutExpressionParser
{
    const PATTERN_SPLITBYOPERATOR = '/\s*(\&\&|\|\|)\s*/';
    const PATTERN_MATCHPOINTCUTDESIGNATOR = '/^\s*(classAnnotatedWith|class|methodAnnotatedWith|method|within|filter|setting|evaluate)/';
    const PATTERN_MATCHVISIBILITYMODIFIER = '/^(public|protected) +/';
    const PATTERN_MATCHRUNTIMEEVALUATIONSDEFINITION = '/(?:
														(?:
															\s*(   "(?:\\\"|[^"])*"
																|\(.*?\)
																|\'(?:\\\\\'|[^\'])*\'
																|[a-zA-Z0-9\-_.]+
															)
															\s*(===?|!==?|<=|>=|<|>|in|contains|matches)\s*
															(   "(?:\\\"|[^"])*"
																|\(.*?\)
																|\'(?:\\\\\'|[^\'])*\'
																|[a-zA-Z0-9\-_.]+
															)
														)
														\s*,{0,1}?
													)+
												/x';
    const PATTERN_MATCHRUNTIMEEVALUATIONSVALUELIST = '/(?:
																	\s*(
																		"(?:\\\"|[^"])*"
																		|\'(?:\\\\\'|[^\'])*\'
																		|(?:[a-zA-Z0-9\-_.])+
																	)
																	\s*,{0,1}?
																)+
																/x';
    const PATTERN_MATCHMETHODNAMEANDARGUMENTS = '/^(?P<MethodName>.*)\((?P<MethodArguments>.*)\)$/';

    /**
     * @var ProxyClassBuilder
     */
    protected $proxyClassBuilder;

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var string
     */
    protected $sourceHint = '';

    /**
     * @param ProxyClassBuilder $proxyClassBuilder
     * @return void
     */
    public function injectProxyClassBuilder(ProxyClassBuilder $proxyClassBuilder)
    {
        $this->proxyClassBuilder = $proxyClassBuilder;
    }

    /**
     * @param ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param ObjectManagerInterface $objectManager
     * @return void
     */
    public function injectObjectManager(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Parses a string pointcut expression and returns the pointcut
     * objects accordingly
     *
     * @param string $pointcutExpression The expression defining the pointcut
     * @param string $sourceHint A message giving a hint on where the expression was defined. This is used in error messages.
     * @return PointcutFilterComposite A composite of class-filters, method-filters and pointcuts
     * @throws InvalidPointcutExpressionException
     * @throws AopException
     */
    public function parse($pointcutExpression, $sourceHint)
    {
        $this->sourceHint = $sourceHint;

        if (!is_string($pointcutExpression) || strlen($pointcutExpression) === 0) {
            throw new InvalidPointcutExpressionException(sprintf('Pointcut expression must be a valid string, "%s" given, defined in "%s"', gettype($pointcutExpression), $this->sourceHint), 1168874738);
        }
        $pointcutFilterComposite = new PointcutFilterComposite();
        $pointcutExpressionParts = preg_split(self::PATTERN_SPLITBYOPERATOR, $pointcutExpression, -1, PREG_SPLIT_DELIM_CAPTURE);

        for ($partIndex = 0; $partIndex < count($pointcutExpressionParts); $partIndex += 2) {
            $operator = ($partIndex > 0) ? trim($pointcutExpressionParts[$partIndex - 1]) : '&&';
            $expression = trim($pointcutExpressionParts[$partIndex]);

            if ($expression[0] === '!') {
                $expression = trim(substr($expression, 1));
                $operator .= '!';
            }

            if (strpos($expression, '(') === false) {
                $this->parseDesignatorPointcut($operator, $expression, $pointcutFilterComposite);
            } else {
                $matches = [];
                $numberOfMatches = preg_match(self::PATTERN_MATCHPOINTCUTDESIGNATOR, $expression, $matches);
                if ($numberOfMatches !== 1) {
                    throw new InvalidPointcutExpressionException('Syntax error: Pointcut designator expected near "' . $expression . '", defined in ' . $this->sourceHint, 1168874739);
                }
                $pointcutDesignator = $matches[0];
                $signaturePattern = $this->getSubstringBetweenParentheses($expression);
                switch ($pointcutDesignator) {
                    case 'classAnnotatedWith':
                    case 'class':
                    case 'methodAnnotatedWith':
                    case 'method':
                    case 'within':
                    case 'filter':
                    case 'setting':
                        $parseMethodName = 'parseDesignator' . ucfirst($pointcutDesignator);
                        $this->$parseMethodName($operator, $signaturePattern, $pointcutFilterComposite);
                    break;
                    case 'evaluate':
                        $this->parseRuntimeEvaluations($operator, $signaturePattern, $pointcutFilterComposite);
                    break;
                    default:
                        throw new AopException('Support for pointcut designator "' . $pointcutDesignator . '" has not been implemented (yet), defined in ' . $this->sourceHint, 1168874740);
                }
            }
        }
        return $pointcutFilterComposite;
    }

    /**
     * Takes a class annotation filter pattern and adds a so configured class annotation filter to the
     * filter composite object.
     *
     * @param string $operator The operator
     * @param string $annotationPattern The pattern expression as configuration for the class annotation filter
     * @param PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the class annotation filter) will be added to this composite object.
     * @return void
     */
    protected function parseDesignatorClassAnnotatedWith($operator, $annotationPattern, PointcutFilterComposite $pointcutFilterComposite)
    {
        $annotationPropertyConstraints = [];
        $this->parseAnnotationPattern($annotationPattern, $annotationPropertyConstraints);

        $filter = new PointcutClassAnnotatedWithFilter($annotationPattern, $annotationPropertyConstraints);
        $filter->injectReflectionService($this->reflectionService);
        $filter->injectSystemLogger($this->objectManager->get(SystemLoggerInterface::class));
        $pointcutFilterComposite->addFilter($operator, $filter);
    }

    /**
     * Takes a class filter pattern and adds a so configured class filter to the
     * filter composite object.
     *
     * @param string $operator The operator
     * @param string $classPattern The pattern expression as configuration for the class filter
     * @param PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the class filter) will be added to this composite object.
     * @return void
     */
    protected function parseDesignatorClass($operator, $classPattern, PointcutFilterComposite $pointcutFilterComposite)
    {
        $filter = new PointcutClassNameFilter($classPattern);
        $filter->injectReflectionService($this->reflectionService);
        $pointcutFilterComposite->addFilter($operator, $filter);
    }

    /**
     * Takes a method annotation filter pattern and adds a so configured method annotation filter to the
     * filter composite object.
     *
     * @param string $operator The operator
     * @param string $annotationPattern The pattern expression as configuration for the method annotation filter
     * @param PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the method annotation filter) will be added to this composite object.
     * @return void
     */
    protected function parseDesignatorMethodAnnotatedWith($operator, $annotationPattern, PointcutFilterComposite $pointcutFilterComposite)
    {
        $annotationPropertyConstraints = [];
        $this->parseAnnotationPattern($annotationPattern, $annotationPropertyConstraints);

        $filter = new PointcutMethodAnnotatedWithFilter($annotationPattern, $annotationPropertyConstraints);
        $filter->injectReflectionService($this->reflectionService);
        $filter->injectSystemLogger($this->objectManager->get(SystemLoggerInterface::class));
        $pointcutFilterComposite->addFilter($operator, $filter);
    }

    /**
     * Parse an annotation pattern and adjust $annotationPattern and $annotationPropertyConstraints as
     * needed.
     *
     * @param string $annotationPattern
     * @param array $annotationPropertyConstraints
     * @return void
     */
    protected function parseAnnotationPattern(&$annotationPattern, array &$annotationPropertyConstraints)
    {
        if (strpos($annotationPattern, '(') !== false) {
            $matches = [];
            preg_match(self::PATTERN_MATCHMETHODNAMEANDARGUMENTS, $annotationPattern, $matches);

            $annotationPattern = $matches['MethodName'];
            $annotationPropertiesPattern = $matches['MethodArguments'];
            $annotationPropertyConstraints = $this->getArgumentConstraintsFromMethodArgumentsPattern($annotationPropertiesPattern);
        }
    }

    /**
     * Splits the parameters of the pointcut designator "method" into a class
     * and a method part and adds the appropriately configured filters to the
     * filter composite object.
     *
     * @param string $operator The operator
     * @param string $signaturePattern The pattern expression defining the class and method - the "signature"
     * @param PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the class and method filter) will be added to this composite object.
     * @return void
     * @throws InvalidPointcutExpressionException if there's an error in the pointcut expression
     */
    protected function parseDesignatorMethod($operator, $signaturePattern, PointcutFilterComposite $pointcutFilterComposite)
    {
        if (strpos($signaturePattern, '->') === false) {
            throw new InvalidPointcutExpressionException('Syntax error: "->" expected in "' . $signaturePattern . '", defined in ' . $this->sourceHint, 1169027339);
        }
        $methodVisibility = $this->getVisibilityFromSignaturePattern($signaturePattern);
        list($classPattern, $methodPattern) = explode('->', $signaturePattern, 2);
        if (strpos($methodPattern, '(') === false) {
            throw new InvalidPointcutExpressionException('Syntax error: "(" expected in "' . $methodPattern . '", defined in ' . $this->sourceHint, 1169144299);
        }

        $matches = [];
        preg_match(self::PATTERN_MATCHMETHODNAMEANDARGUMENTS, $methodPattern, $matches);

        $methodNamePattern = $matches['MethodName'];
        $methodArgumentPattern = $matches['MethodArguments'];
        $methodArgumentConstraints = $this->getArgumentConstraintsFromMethodArgumentsPattern($methodArgumentPattern);

        $classNameFilter = new PointcutClassNameFilter($classPattern);
        $classNameFilter->injectReflectionService($this->reflectionService);
        $methodNameFilter = new PointcutMethodNameFilter($methodNamePattern, $methodVisibility, $methodArgumentConstraints);
        /** @var SystemLoggerInterface $systemLogger */
        $systemLogger = $this->objectManager->get(SystemLoggerInterface::class);
        $methodNameFilter->injectSystemLogger($systemLogger);
        $methodNameFilter->injectReflectionService($this->reflectionService);

        if ($operator !== '&&') {
            $subComposite = new PointcutFilterComposite();
            $subComposite->addFilter('&&', $classNameFilter);
            $subComposite->addFilter('&&', $methodNameFilter);

            $pointcutFilterComposite->addFilter($operator, $subComposite);
        } else {
            $pointcutFilterComposite->addFilter('&&', $classNameFilter);
            $pointcutFilterComposite->addFilter('&&', $methodNameFilter);
        }
    }

    /**
     * Adds a class type filter to the pointcut filter composite
     *
     * @param string $operator
     * @param string $signaturePattern The pattern expression defining the class type
     * @param PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the class type filter) will be added to this composite object.
     * @return void
     */
    protected function parseDesignatorWithin($operator, $signaturePattern, PointcutFilterComposite $pointcutFilterComposite)
    {
        $filter = new PointcutClassTypeFilter($signaturePattern);
        $filter->injectReflectionService($this->reflectionService);
        $pointcutFilterComposite->addFilter($operator, $filter);
    }

    /**
     * Splits the value of the pointcut designator "pointcut" into an aspect
     * class- and a pointcut method part and adds the appropriately configured
     * filter to the composite object.
     *
     * @param string $operator The operator
     * @param string $pointcutExpression The pointcut expression (value of the designator)
     * @param PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the pointcut filter) will be added to this composite object.
     * @return void
     * @throws InvalidPointcutExpressionException
     */
    protected function parseDesignatorPointcut($operator, $pointcutExpression, PointcutFilterComposite $pointcutFilterComposite)
    {
        if (strpos($pointcutExpression, '->') === false) {
            throw new InvalidPointcutExpressionException('Syntax error: "->" expected in "' . $pointcutExpression . '", defined in ' . $this->sourceHint, 1172219205);
        }
        list($aspectClassName, $pointcutMethodName) = explode('->', $pointcutExpression, 2);
        $pointcutFilter = new PointcutFilter($aspectClassName, $pointcutMethodName);
        $pointcutFilter->injectProxyClassBuilder($this->proxyClassBuilder);
        $pointcutFilterComposite->addFilter($operator, $pointcutFilter);
    }

    /**
     * Adds a custom filter to the pointcut filter composite
     *
     * @param string $operator The operator
     * @param string $filterObjectName Object Name of the custom filter (value of the designator)
     * @param PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the custom filter) will be added to this composite object.
     * @return void
     * @throws InvalidPointcutExpressionException
     */
    protected function parseDesignatorFilter($operator, $filterObjectName, PointcutFilterComposite $pointcutFilterComposite)
    {
        $customFilter = $this->objectManager->get($filterObjectName);
        if (!$customFilter instanceof PointcutFilterInterface) {
            throw new InvalidPointcutExpressionException('Invalid custom filter: "' . $filterObjectName . '" does not implement the required PointcutFilterInterface, defined in ' . $this->sourceHint, 1231871755);
        }
        $pointcutFilterComposite->addFilter($operator, $customFilter);
    }

    /**
     * Adds a setting filter to the pointcut filter composite
     *
     * @param string $operator The operator
     * @param string $configurationPath The path to the settings option, that should be used
     * @param PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the custom filter) will be added to this composite object.
     * @return void
     */
    protected function parseDesignatorSetting($operator, $configurationPath, PointcutFilterComposite $pointcutFilterComposite)
    {
        $filter = new PointcutSettingFilter($configurationPath);
        /** @var ConfigurationManager $configurationManager */
        $configurationManager = $this->objectManager->get(ConfigurationManager::class);
        $filter->injectConfigurationManager($configurationManager);

        $pointcutFilterComposite->addFilter($operator, $filter);
    }

    /**
     * Adds runtime evaluations to the pointcut filter composite
     *
     * @param string $operator The operator
     * @param string $runtimeEvaluations The runtime evaluations string
     * @param PointcutFilterComposite $pointcutFilterComposite An instance of the pointcut filter composite. The result (ie. the custom filter) will be added to this composite object.
     * @return void
     */
    protected function parseRuntimeEvaluations($operator, $runtimeEvaluations, PointcutFilterComposite $pointcutFilterComposite)
    {
        $runtimeEvaluationsDefinition = [
            $operator => [
                'evaluateConditions' => $this->getRuntimeEvaluationConditionsFromEvaluateString($runtimeEvaluations)
            ]
        ];

        $pointcutFilterComposite->setGlobalRuntimeEvaluationsDefinition($runtimeEvaluationsDefinition);
    }

    /**
     * Returns the substring of $string which is enclosed by parentheses
     * of the first level.
     *
     * @param string $string The string to parse
     * @return string The inner part between the first level of parentheses
     * @throws InvalidPointcutExpressionException
     */
    protected function getSubstringBetweenParentheses($string)
    {
        $startingPosition = 0;
        $openParentheses = 0;
        $substring = '';

        for ($i = $startingPosition; $i < strlen($string); $i++) {
            if ($string[$i] === ')') {
                $openParentheses--;
            }
            if ($openParentheses > 0) {
                $substring .= $string{$i};
            }
            if ($string[$i] === '(') {
                $openParentheses++;
            }
        }
        if ($openParentheses < 0) {
            throw new InvalidPointcutExpressionException('Pointcut expression is in excess of ' . abs($openParentheses) . ' closing parenthesis/es, defined in ' . $this->sourceHint, 1168966689);
        }
        if ($openParentheses > 0) {
            throw new InvalidPointcutExpressionException('Pointcut expression lacks of ' . $openParentheses . ' closing parenthesis/es, defined in ' . $this->sourceHint, 1168966690);
        }
        return $substring;
    }

    /**
     * Parses the signature pattern and returns the visibility modifier if any. If a modifier
     * was found, it will be removed from the $signaturePattern.
     *
     * @param string &$signaturePattern The regular expression for matching the method() signature
     * @return string Visibility modifier or NULL of none was found
     * @throws InvalidPointcutExpressionException
     */
    protected function getVisibilityFromSignaturePattern(&$signaturePattern)
    {
        $visibility = null;
        $matches = [];
        $numberOfMatches = preg_match_all(self::PATTERN_MATCHVISIBILITYMODIFIER, $signaturePattern, $matches, PREG_SET_ORDER);
        if ($numberOfMatches > 1) {
            throw new InvalidPointcutExpressionException('Syntax error: method name expected after visibility modifier in "' . $signaturePattern . '", defined in ' . $this->sourceHint, 1172492754);
        }
        if ($numberOfMatches === false) {
            throw new InvalidPointcutExpressionException('Error while matching visibility modifier in "' . $signaturePattern . '", defined in ' . $this->sourceHint, 1172492967);
        }
        if ($numberOfMatches === 1) {
            $visibility = $matches[0][1];
            $signaturePattern = trim(substr($signaturePattern, strlen($visibility)));
        }
        return $visibility;
    }

    /**
    * Parses the method arguments pattern and returns the corresponding constraints array
    *
    * @param string $methodArgumentsPattern The arguments pattern defined in the pointcut expression
    * @return array The corresponding constraints array
    */
    protected function getArgumentConstraintsFromMethodArgumentsPattern($methodArgumentsPattern)
    {
        $matches = [];
        $argumentConstraints = [];

        preg_match_all(self::PATTERN_MATCHRUNTIMEEVALUATIONSDEFINITION, $methodArgumentsPattern, $matches);

        for ($i = 0; $i < count($matches[0]); $i++) {
            if ($matches[2][$i] === 'in' || $matches[2][$i] === 'matches') {
                $list = [];
                $listEntries = [];

                if (preg_match('/^\s*\(.*\)\s*$/', $matches[3][$i], $list) > 0) {
                    preg_match_all(self::PATTERN_MATCHRUNTIMEEVALUATIONSVALUELIST, $list[0], $listEntries);
                    $matches[3][$i] = $listEntries[1];
                }
            }

            $argumentConstraints[$matches[1][$i]]['operator'][] = $matches[2][$i];
            $argumentConstraints[$matches[1][$i]]['value'][] = $matches[3][$i];
        }
        return $argumentConstraints;
    }

    /**
     * Parses the evaluate string for runtime evaluations and returns the corresponding conditions array
     *
     * @param string $evaluateString The evaluate string defined in the pointcut expression
     * @return array The corresponding constraints array
     */
    protected function getRuntimeEvaluationConditionsFromEvaluateString($evaluateString)
    {
        $matches = [];
        $runtimeEvaluationConditions = [];

        preg_match_all(self::PATTERN_MATCHRUNTIMEEVALUATIONSDEFINITION, $evaluateString, $matches);

        $matchesCount = count($matches[0]);
        for ($i = 0; $i < $matchesCount; $i++) {
            if ($matches[2][$i] === 'in' || $matches[2][$i] === 'matches') {
                $list = [];
                $listEntries = [];

                if (preg_match('/^\s*\(.*\)\s*$/', $matches[3][$i], $list) > 0) {
                    preg_match_all(self::PATTERN_MATCHRUNTIMEEVALUATIONSVALUELIST, $list[0], $listEntries);
                    $matches[3][$i] = $listEntries[1];
                }
            }

            $runtimeEvaluationConditions[] = [
                'operator' => $matches[2][$i],
                'leftValue' => $matches[1][$i],
                'rightValue' => $matches[3][$i],
            ];
        }
        return $runtimeEvaluationConditions;
    }
}

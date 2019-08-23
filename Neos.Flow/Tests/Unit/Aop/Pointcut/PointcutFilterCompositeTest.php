<?php
namespace Neos\Flow\Tests\Unit\Aop\Pointcut;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Aop\Pointcut;
use Neos\Flow\Aop;

/**
 * Testcase for the Pointcut Filter Composite
 */
class PointcutFilterCompositeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getRuntimeEvaluationsDefintionReturnsTheEvaluationsFromAllContainedFiltersThatMatchedThePointcutWithTheCorrectOperators()
    {
        $runtimeEvaluations1 = ['methodArgumentConstraint' => ['arg1' => 'eval1']];
        $runtimeEvaluations2 = ['methodArgumentConstraint' => ['arg2' => 'eval2']];
        $runtimeEvaluations3 = ['methodArgumentConstraint' => ['arg3' => 'eval3']];
        $runtimeEvaluations4 = ['methodArgumentConstraint' => ['arg4' => 'eval4']];
        $runtimeEvaluations5 = ['methodArgumentConstraint' => ['arg5' => 'eval5', 'arg6' => 'eval6']];

        $mockPointcutFilter1 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter1->expects(self::once())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue($runtimeEvaluations1));
        $mockPointcutFilter1->expects(self::any())->method('matches')->will(self::returnValue(true));
        $mockPointcutFilter1->expects(self::any())->method('hasRuntimeEvaluationsDefinition')->will(self::returnValue(true));

        $mockPointcutFilter2 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter2->expects(self::once())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue($runtimeEvaluations2));
        $mockPointcutFilter2->expects(self::any())->method('matches')->will(self::returnValue(false));
        $mockPointcutFilter2->expects(self::any())->method('hasRuntimeEvaluationsDefinition')->will(self::returnValue(true));

        $mockPointcutFilter3 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter3->expects(self::once())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue($runtimeEvaluations3));
        $mockPointcutFilter3->expects(self::any())->method('matches')->will(self::returnValue(true));
        $mockPointcutFilter3->expects(self::any())->method('hasRuntimeEvaluationsDefinition')->will(self::returnValue(true));

        $mockPointcutFilter4 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter4->expects(self::once())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue($runtimeEvaluations4));
        $mockPointcutFilter4->expects(self::any())->method('matches')->will(self::returnValue(true));
        $mockPointcutFilter4->expects(self::any())->method('hasRuntimeEvaluationsDefinition')->will(self::returnValue(true));

        $mockPointcutFilter5 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter5->expects(self::once())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue($runtimeEvaluations5));
        $mockPointcutFilter5->expects(self::any())->method('matches')->will(self::returnValue(true));
        $mockPointcutFilter5->expects(self::any())->method('hasRuntimeEvaluationsDefinition')->will(self::returnValue(true));

        $pointcutFilterComposite = new Pointcut\PointcutFilterComposite();
        $pointcutFilterComposite->addFilter('&&', $mockPointcutFilter1);
        $pointcutFilterComposite->addFilter('&&!', $mockPointcutFilter2);
        $pointcutFilterComposite->addFilter('||', $mockPointcutFilter3);
        $pointcutFilterComposite->addFilter('||!', $mockPointcutFilter4);
        $pointcutFilterComposite->addFilter('||!', $mockPointcutFilter5);

        $expectedRuntimeEvaluations = [
            '&&' => [
                'methodArgumentConstraint' => ['arg1' => 'eval1']
            ],
            '||' => [
                'methodArgumentConstraint' => ['arg3' => 'eval3']
            ],
            '||!' => [
                'methodArgumentConstraint' => ['arg4' => 'eval4', 'arg5' => 'eval5', 'arg6' => 'eval6']
            ]
        ];

        $pointcutFilterComposite->matches('className', 'methodName', 'methodDeclaringClassName', 1);

        self::assertEquals($expectedRuntimeEvaluations, $pointcutFilterComposite->getRuntimeEvaluationsDefinition());
    }

    /**
     * @test
     */
    public function matchesReturnsTrueForNegatedSubfiltersWithRuntimeEvaluations()
    {
        $mockPointcutFilter1 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter1->expects(self::any())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['eval']));
        $mockPointcutFilter1->expects(self::once())->method('matches')->will(self::returnValue(true));

        $mockPointcutFilter2 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter2->expects(self::any())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['eval']));
        $mockPointcutFilter2->expects(self::once())->method('matches')->will(self::returnValue(true));

        $mockPointcutFilter3 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter3->expects(self::any())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['eval']));
        $mockPointcutFilter3->expects(self::any())->method('matches')->will(self::returnValue(true));

        $mockPointcutFilter4 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter4->expects(self::any())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['eval']));
        $mockPointcutFilter4->expects(self::once())->method('matches')->will(self::returnValue(true));

        $pointcutFilterComposite = new Pointcut\PointcutFilterComposite();
        $pointcutFilterComposite->addFilter('&&', $mockPointcutFilter1);
        $pointcutFilterComposite->addFilter('&&!', $mockPointcutFilter2);
        $pointcutFilterComposite->addFilter('||', $mockPointcutFilter3);
        $pointcutFilterComposite->addFilter('||!', $mockPointcutFilter4);

        self::assertTrue($pointcutFilterComposite->matches('someClass', 'someMethod', 'someDeclaringClass', 1));
    }

    /**
     * @test
     */
    public function matchesReturnsTrueForNegatedSubfilter()
    {
        $mockPointcutFilter1 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter1->expects(self::any())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['eval']));
        $mockPointcutFilter1->expects(self::once())->method('matches')->will(self::returnValue(true));

        $mockPointcutFilter2 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter2->expects(self::any())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['eval']));
        $mockPointcutFilter2->expects(self::once())->method('matches')->will(self::returnValue(false));

        $pointcutFilterComposite = new Pointcut\PointcutFilterComposite();
        $pointcutFilterComposite->addFilter('&&', $mockPointcutFilter1);
        $pointcutFilterComposite->addFilter('&&!', $mockPointcutFilter2);

        self::assertTrue($pointcutFilterComposite->matches('someClass', 'someMethod', 'someDeclaringClass', 1));
    }

    /**
     * @test
     */
    public function matchesReturnsFalseEarlyForAndedSubfilters()
    {
        $mockPointcutFilter1 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter1->expects(self::any())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['eval']));
        $mockPointcutFilter1->expects(self::once())->method('matches')->will(self::returnValue(false));

        $mockPointcutFilter2 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter2->expects(self::any())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['eval']));
        $mockPointcutFilter2->expects(self::never())->method('matches')->will(self::returnValue(false));

        $pointcutFilterComposite = new Pointcut\PointcutFilterComposite();
        $pointcutFilterComposite->addFilter('&&', $mockPointcutFilter1);
        $pointcutFilterComposite->addFilter('&&!', $mockPointcutFilter2);

        self::assertFalse($pointcutFilterComposite->matches('someClass', 'someMethod', 'someDeclaringClass', 1));
    }

    /**
     * @test
     */
    public function matchesReturnsFalseEarlyForAndedNegatedSubfilters()
    {
        $mockPointcutFilter1 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter1->expects(self::any())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['eval']));
        $mockPointcutFilter1->expects(self::once())->method('matches')->will(self::returnValue(true));

        $mockPointcutFilter2 = $this->getMockBuilder(Pointcut\PointcutFilterInterface::class)->disableOriginalConstructor()->getMock();
        $mockPointcutFilter2->expects(self::any())->method('getRuntimeEvaluationsDefinition')->will(self::returnValue(['eval']));
        $mockPointcutFilter2->expects(self::never())->method('matches')->will(self::returnValue(true));

        $pointcutFilterComposite = new Pointcut\PointcutFilterComposite();
        $pointcutFilterComposite->addFilter('&&!', $mockPointcutFilter1);
        $pointcutFilterComposite->addFilter('&&', $mockPointcutFilter2);

        self::assertFalse($pointcutFilterComposite->matches('someClass', 'someMethod', 'someDeclaringClass', 1));
    }

    /**
     * @test
     */
    public function globalRuntimeEvaluationsDefinitionAreAddedCorrectlyToThePointcutFilterComposite()
    {
        $existingRuntimeEvaluationsDefintion = [
                                                '&&' => [
                                                    '&&' => [
                                                        'methodArgumentConstraints' => [
                                                            'usage' => [
                                                                'operator' => 'in',
                                                                'value' => ['\'usage1\'', '\'usage2\'', '"usage3"']
                                                            ]
                                                        ]
                                                    ]
                                                ]
        ];

        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);
        $pointcutFilterComposite->_set('runtimeEvaluationsDefinition', $existingRuntimeEvaluationsDefintion);

        $newRuntimeEvaluationsDefinition = [
                                                '&&' => [
                                                    'evaluateConditions' => [
                                                        [
                                                            'operator' => '==',
                                                            'leftValue' => '"bar"',
                                                            'rightValue' => 4,
                                                        ]
                                                    ]
                                                ]
        ];

        $pointcutFilterComposite->setGlobalRuntimeEvaluationsDefinition($newRuntimeEvaluationsDefinition);

        $expectedResult = [
                            '&&' => [
                                '&&' => [
                                    'methodArgumentConstraints' => [
                                        'usage' => [
                                            'operator' => 'in',
                                            'value' => ['\'usage1\'', '\'usage2\'', '"usage3"']
                                        ]
                                    ]
                                ],
                                'evaluateConditions' => [
                                    [
                                        'operator' => '==',
                                        'leftValue' => '"bar"',
                                        'rightValue' => 4,
                                    ]
                                ]
                            ]
        ];

        self::assertEquals($expectedResult, $pointcutFilterComposite->getRuntimeEvaluationsDefinition(), 'The runtime evaluations definition has not been added correctly to the pointcut filter composite.');
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationsClosureCodeReturnsTheCorrectStringForBasicRuntimeEvaluationsDefintion()
    {
        $runtimeEvaluationsDefintion = [
                                        '&&' => [
                                            '&&' => [
                                                '&&' => [
                                                    'evaluateConditions' => [
                                                        0 => [
                                                            'operator' => '!=',
                                                            'leftValue' => 'this.some.thing',
                                                            'rightValue' => 'current.party.name',
                                                        ]],
                                                    '&&' => [
                                                        'methodArgumentConstraints' => [
                                                            'identifier' => [
                                                                'operator' => [
                                                                    0 => '>',
                                                                    1 => '<='
                                                                ],
                                                                'value' => [
                                                                    0 => '3',
                                                                    1 => '5'
                                                                ]]]]]]],
                                        '||' => [
                                            '&&' => [
                                                'methodArgumentConstraints' => [
                                                    'identifier' => [
                                                        'operator' => ['=='],
                                                        'value' => ['42']
                                                    ]]]]];

        $expectedResult = "function(\\Neos\\Flow\\Aop\\JoinPointInterface \$joinPoint, \$objectManager) {\n" .
                                "    \$currentObject = \$joinPoint->getProxy();\n" .
                                "    \$globalObjectNames = \$objectManager->get(\Neos\Flow\Configuration\ConfigurationManager::class)->getConfiguration(\Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.aop.globalObjects');\n" .
                                "    \$globalObjects = array_map(function(\$objectName) use (\$objectManager) { return \$objectManager->get(\$objectName); }, \$globalObjectNames);\n" .
                                "    return (((\Neos\Utility\ObjectAccess::getPropertyPath(\$currentObject, 'some.thing') != \Neos\Utility\ObjectAccess::getPropertyPath(\$globalObjects['party'], 'name')) && (\$joinPoint->getMethodArgument('identifier') > 3 && \$joinPoint->getMethodArgument('identifier') <= 5)) || (\$joinPoint->getMethodArgument('identifier') == 42));\n" .
                                "}";

        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);
        $pointcutFilterComposite->_set('runtimeEvaluationsDefinition', $runtimeEvaluationsDefintion);

        $result = $pointcutFilterComposite->getRuntimeEvaluationsClosureCode();

        self::assertTrue($pointcutFilterComposite->_call('hasRuntimeEvaluationsDefinition'));
        self::assertEquals($expectedResult, $result, 'The wrong Code has been built.');
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationsClosureCodeHandlesDefinitionsConcatenatedByNegatedOperatorsCorrectly()
    {
        $runtimeEvaluationsDefintion = [
                                        '&&' => [
                                            '&&' => [
                                                '&&' => [
                                                    'evaluateConditions' => [
                                                        0 => [
                                                            'operator' => '!=',
                                                            'leftValue' => 'this.some.thing',
                                                            'rightValue' => 'current.party.name',
                                                        ]],
                                                    '&&!' => [
                                                        'methodArgumentConstraints' => [
                                                            'identifier' => [
                                                                'operator' => [
                                                                    0 => '>',
                                                                    1 => '<='
                                                                ],
                                                                'value' => [
                                                                    0 => '3',
                                                                    1 => '5'
                                                                ]]]]]]],
                                        '||!' => [
                                            '&&' => [
                                                'methodArgumentConstraints' => [
                                                    'identifier' => [
                                                        'operator' => ['=='],
                                                        'value' => ['42']
                                                    ]]]]];

        $expectedResult = "function(\\Neos\\Flow\\Aop\\JoinPointInterface \$joinPoint, \$objectManager) {\n" .
                                "    \$currentObject = \$joinPoint->getProxy();\n" .
                                "    \$globalObjectNames = \$objectManager->get(\Neos\Flow\Configuration\ConfigurationManager::class)->getConfiguration(\Neos\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.aop.globalObjects');\n" .
                                "    \$globalObjects = array_map(function(\$objectName) use (\$objectManager) { return \$objectManager->get(\$objectName); }, \$globalObjectNames);\n" .
                                "    return (((\Neos\Utility\ObjectAccess::getPropertyPath(\$currentObject, 'some.thing') != \Neos\Utility\ObjectAccess::getPropertyPath(\$globalObjects['party'], 'name')) && (!(\$joinPoint->getMethodArgument('identifier') > 3 && \$joinPoint->getMethodArgument('identifier') <= 5))) || (!(\$joinPoint->getMethodArgument('identifier') == 42)));\n" .
                                "}";

        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);
        $pointcutFilterComposite->_set('runtimeEvaluationsDefinition', $runtimeEvaluationsDefintion);

        $result = $pointcutFilterComposite->getRuntimeEvaluationsClosureCode();

        self::assertTrue($pointcutFilterComposite->_call('hasRuntimeEvaluationsDefinition'));
        self::assertEquals($expectedResult, $result, 'The wrong Code has been built.');
    }

    /**
     * @test
     */
    public function getRuntimeEvaluationsClosureCodeReturnsTheCorrectStringForAnEmptyDefinition()
    {
        $expectedResult = 'NULL';

        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);
        $pointcutFilterComposite->_set('runtimeEvaluationsDefinition', []);

        $result = $pointcutFilterComposite->getRuntimeEvaluationsClosureCode();

        self::assertFalse($pointcutFilterComposite->_call('hasRuntimeEvaluationsDefinition'));
        self::assertEquals($expectedResult, $result, 'The wrong Code has been built.');
    }

    /**
     * @test
     */
    public function buildMethodArgumentsEvaluationConditionCodeBuildsTheCorrectCodeForAnArgumentWithMoreThanOneCondition()
    {
        $condition = [
                                'identifier' => [
                                    'operator' => [
                                        0 => '>',
                                        1 => '<='
                                    ],
                                    'value' => [
                                        0 => '3',
                                        1 => '5'
                                    ]
                                ]
        ];

        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);

        $result = $pointcutFilterComposite->_call('buildMethodArgumentsEvaluationConditionCode', $condition);

        $expectedResult = '($joinPoint->getMethodArgument(\'identifier\') > 3 && $joinPoint->getMethodArgument(\'identifier\') <= 5)';

        self::assertEquals($expectedResult, $result, 'The wrong Code has been built.');
    }

    /**
     * @test
     */
    public function buildMethodArgumentsEvaluationConditionCodeBuildsTheCorrectCodeForAConditionWithObjectAccess()
    {
        $condition = [
                                'identifier' => [
                                    'operator' => [
                                        0 => '==',
                                        1 => '!='
                                    ],
                                    'value' => [
                                        0 => 'this.bar.baz',
                                        1 => 'current.party.bar.baz'
                                    ]
                                ],
                                'some.object.property' => [
                                    'operator' => [
                                        0 => '!='
                                    ],
                                    'value' => [
                                        0 => 'this.object.with.another.property'
                                    ]
                                ]
        ];

        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);

        $result = $pointcutFilterComposite->_call('buildMethodArgumentsEvaluationConditionCode', $condition);

        $expectedResult = '($joinPoint->getMethodArgument(\'identifier\') == \Neos\Utility\ObjectAccess::getPropertyPath($currentObject, \'bar.baz\') && $joinPoint->getMethodArgument(\'identifier\') != \Neos\Utility\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'bar.baz\') && \Neos\Utility\ObjectAccess::getPropertyPath($joinPoint->getMethodArgument(\'some\'), \'object.property\') != \Neos\Utility\ObjectAccess::getPropertyPath($currentObject, \'object.with.another.property\'))';

        self::assertEquals($expectedResult, $result, 'The wrong Code has been built.');
    }

    /**
     * @test
     */
    public function buildMethodArgumentsEvaluationConditionCodeBuildsTheCorrectCodeForAConditionWithInOperator()
    {
        $condition = [
                                'identifier' => [
                                    'operator' => [
                                        0 => 'in',
                                    ],
                                    'value' => [
                                        0 => ['\'usage1\'', '\'usage2\'', '"usage3"'],
                                    ]
                                ]
        ];

        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);

        $result = $pointcutFilterComposite->_call('buildMethodArgumentsEvaluationConditionCode', $condition);

        $expectedResult = '((array(\'usage1\', \'usage2\', "usage3") instanceof \SplObjectStorage || array(\'usage1\', \'usage2\', "usage3") instanceof \Doctrine\Common\Collections\Collection ? $joinPoint->getMethodArgument(\'identifier\') !== NULL && array(\'usage1\', \'usage2\', "usage3")->contains($joinPoint->getMethodArgument(\'identifier\')) : in_array($joinPoint->getMethodArgument(\'identifier\'), array(\'usage1\', \'usage2\', "usage3"))))';

        self::assertEquals($expectedResult, $result, 'The wrong Code has been built.');
    }

    /**
     * @test
     */
    public function buildMethodArgumentsEvaluationConditionCodeBuildsTheCorrectCodeForAConditionWithMatchesOperator()
    {
        $condition = [
                                'identifier' => [
                                    'operator' => [
                                        0 => 'matches',
                                        1 => 'matches'
                                    ],
                                    'value' => [
                                        0 => ['\'usage1\'', '\'usage2\'', '"usage3"'],
                                        1 => 'this.accounts'
                                    ]
                                ]
        ];

        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);

        $result = $pointcutFilterComposite->_call('buildMethodArgumentsEvaluationConditionCode', $condition);

        $expectedResult = '((!empty(array_intersect($joinPoint->getMethodArgument(\'identifier\'), array(\'usage1\', \'usage2\', "usage3")))) && (!empty(array_intersect($joinPoint->getMethodArgument(\'identifier\'), \Neos\Utility\ObjectAccess::getPropertyPath($currentObject, \'accounts\')))))';

        self::assertEquals($expectedResult, $result, 'The wrong Code has been built.');
    }

    /**
     * @test
     */
    public function buildGlobalRuntimeEvaluationsConditionCodeBuildsTheCorrectCodeForConditionsWithObjectAccess()
    {
        $condition = [
                            0 => [
                                'operator' => '!=',
                                'leftValue' => 'this.some.thing',
                                'rightValue' => 'current.party.name',
                            ],
                            1 => [
                                'operator' => '==',
                                'leftValue' => 'current.party.account.accountIdentifier',
                                'rightValue' => '"admin"',
                            ]
        ];

        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);

        $result = $pointcutFilterComposite->_call('buildGlobalRuntimeEvaluationsConditionCode', $condition);

        $expectedResult = '(\Neos\Utility\ObjectAccess::getPropertyPath($currentObject, \'some.thing\') != \Neos\Utility\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\') && \Neos\Utility\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'account.accountIdentifier\') == "admin")';

        self::assertEquals($expectedResult, $result, 'The wrong Code has been built.');
    }

    /**
     * @test
     */
    public function buildGlobalRuntimeEvaluationsConditionCodeBuildsTheCorrectCodeForAConditionWithInOperator()
    {
        $condition = [
                                0 => [
                                    'operator' => 'in',
                                    'leftValue' => 'this.some.thing',
                                    'rightValue' => ['"foo"', 'current.party.name', 5],
                                ]
        ];

        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);

        $result = $pointcutFilterComposite->_call('buildGlobalRuntimeEvaluationsConditionCode', $condition);

        $expectedResult = '((array("foo", \Neos\Utility\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\'), 5) instanceof \SplObjectStorage || array("foo", \Neos\Utility\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\'), 5) instanceof \Doctrine\Common\Collections\Collection ? \Neos\Utility\ObjectAccess::getPropertyPath($currentObject, \'some.thing\') !== NULL && array("foo", \Neos\Utility\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\'), 5)->contains(\Neos\Utility\ObjectAccess::getPropertyPath($currentObject, \'some.thing\')) : in_array(\Neos\Utility\ObjectAccess::getPropertyPath($currentObject, \'some.thing\'), array("foo", \Neos\Utility\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\'), 5))))';

        self::assertEquals($expectedResult, $result, 'The wrong Code has been built.');
    }

    /**
     * @test
     */
    public function buildGlobalRuntimeEvaluationsConditionCodeBuildsTheCorrectCodeForAConditionWithMatchesOperator()
    {
        $condition = [
                                0 => [
                                    'operator' => 'matches',
                                    'leftValue' => 'this.some.thing',
                                    'rightValue' => ['"foo"', 'current.party.name', 5],
                                ],
                                1 => [
                                    'operator' => 'matches',
                                    'leftValue' => 'this.some.thing',
                                    'rightValue' => 'current.party.accounts',
                                ]
        ];

        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);

        $result = $pointcutFilterComposite->_call('buildGlobalRuntimeEvaluationsConditionCode', $condition);

        $expectedResult = '((!empty(array_intersect(\Neos\Utility\ObjectAccess::getPropertyPath($currentObject, \'some.thing\'), array("foo", \Neos\Utility\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'name\'), 5)))) && (!empty(array_intersect(\Neos\Utility\ObjectAccess::getPropertyPath($currentObject, \'some.thing\'), \Neos\Utility\ObjectAccess::getPropertyPath($globalObjects[\'party\'], \'accounts\')))))';

        self::assertEquals($expectedResult, $result, 'The wrong Code has been built.');
    }

    /**
     * @test
     */
    public function hasRuntimeEvaluationsDefinitionConsidersGlobalAndFilterRuntimeEvaluationsDefinitions()
    {
        $pointcutFilterComposite = $this->getAccessibleMock(Pointcut\PointcutFilterComposite::class, ['dummy'], [], '', false);
        self::assertFalse($pointcutFilterComposite->hasRuntimeEvaluationsDefinition());

        $pointcutFilterComposite->_set('globalRuntimeEvaluationsDefinition', ['foo', 'bar']);
        $pointcutFilterComposite->_set('runtimeEvaluationsDefinition', []);
        self::assertTrue($pointcutFilterComposite->hasRuntimeEvaluationsDefinition());

        $pointcutFilterComposite->_set('globalRuntimeEvaluationsDefinition', []);
        $pointcutFilterComposite->_set('runtimeEvaluationsDefinition', ['bar']);
        self::assertTrue($pointcutFilterComposite->hasRuntimeEvaluationsDefinition());
    }

    /**
     * @test
     */
    public function reduceTargetClassNamesFiltersAllClassesNotMatchedAByClassNameFilter()
    {
        $availableClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Class2',
            'TestPackage\Subpackage\SubSubPackage\Class3',
            'TestPackage\Subpackage2\Class4'
        ];
        sort($availableClassNames);
        $availableClassNamesIndex = new Aop\Builder\ClassNameIndex();
        $availableClassNamesIndex->setClassNames($availableClassNames);

        $classNameFilter1 = new Pointcut\PointcutClassNameFilter('TestPackage\Subpackage\SubSubPackage\Class3');
        $classNameFilter2 = new Pointcut\PointcutClassNameFilter('TestPackage\Subpackage\Class1');
        $methodNameFilter1 = new Pointcut\PointcutMethodNameFilter('method2');

        $expectedClassNames = [
            'TestPackage\Subpackage\Class1',
            'TestPackage\Subpackage\SubSubPackage\Class3'
        ];
        sort($expectedClassNames);
        $expectedClassNamesIndex = new Aop\Builder\ClassNameIndex();
        $expectedClassNamesIndex->setClassNames($expectedClassNames);

        $pointcutFilterComposite = new Pointcut\PointcutFilterComposite();
        $pointcutFilterComposite->addFilter('&&', $classNameFilter1);
        $pointcutFilterComposite->addFilter('||', $classNameFilter2);
        $pointcutFilterComposite->addFilter('&&', $methodNameFilter1);

        $result = $pointcutFilterComposite->reduceTargetClassNames($availableClassNamesIndex);

        self::assertEquals($expectedClassNamesIndex, $result, 'The wrong class names have been filtered');
    }
}

<?php
namespace Neos\Eel;
// @codingStandardsIgnoreFile

/*
 * This file is part of the Neos.Eel package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/*!* !insert_autogen_warning */

/**
 * Eel parser
 *
 * This parser can evaluate the expression language for Flow and uses
 * the basic types from AbstractParser.
 *
 * @Neos\Flow\Annotations\Proxy(false)
 */
class EelParser extends \Neos\Eel\AbstractParser {

/*!* BasicTypes
OffsetAccess: '[' < Expression > ']'
MethodCall: Identifier '(' < Expression? > (',' < Expression > )* ')'
ObjectPath: (MethodCall | Identifier) ('.' (MethodCall | Identifier) | OffsetAccess)*
Term: term:BooleanLiteral !Identifier | term:NumberLiteral | term:StringLiteral | term:ObjectPath
*/

/*!* CombinedExpressions
Expression: exp:ConditionalExpression
SimpleExpression: term:WrappedExpression | term:NotExpression | term:ArrayLiteral | term:ObjectLiteral | term:Term
WrappedExpression: '(' < Expression > ')'
NotExpression: (/ ! | not\s+ /) > exp:SimpleExpression
ConditionalExpression: cond:Disjunction (< '?' > then:Expression < ':' > else:Expression)?
Disjunction: lft:Conjunction (< / \|\| | or\s+ / > rgt:Conjunction)*
Conjunction: lft:Comparison (< / && | and\s+ / > rgt:Comparison)*
Comparison: lft:SumCalculation (< comp:/ == | != | <= | >= | < | > / > rgt:SumCalculation)?
SumCalculation: lft:ProdCalculation (< op:/ \+ | \- / > rgt:ProdCalculation)*
ProdCalculation: lft:SimpleExpression (< op:/ \/ | \* | % / > rgt:SimpleExpression)*
ArrayLiteral: '[' < Expression? (< ',' > Expression)* > ']'
ObjectLiteralProperty: key:(StringLiteral | Identifier) < ':' > value:Expression
ObjectLiteral: '{' ObjectLiteralProperty? (< ',' > ObjectLiteralProperty)* > '}'
*/

}

<?php
namespace TYPO3\Eel;
// @codingStandardsIgnoreFile

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/*!* !insert_autogen_warning */

/**
 * Eel parser
 *
 * This parser can evaluate the expression language for Flow and uses
 * the basic types from AbstractParser.
 */
class EelParser extends \TYPO3\Eel\AbstractParser {

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

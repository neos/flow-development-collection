<?php
namespace TYPO3\Eel;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.Eel".                  *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/*!* !insert_autogen_warning */

/**
 * Eel parser
 *
 * This parser can evaluate the expression language for FLOW3 and uses the
 * basic types from AbstractParser.
 */
class EelParser extends \TYPO3\Eel\AbstractParser {

/*!* BasicTypes
OffsetAccess: '[' < Expression > ']'
MethodCall: Identifier '(' < Expression? > (',' < Expression > )* ')'
ObjectPath: (MethodCall | Identifier) ('.' (MethodCall | Identifier) | OffsetAccess)*
Term: term:BooleanLiteral !Identifier | term:NumberLiteral | term:StringLiteral | term:ObjectPath
*/

/*!* CombinedExpressions
Expression: Disjunction
SimpleExpression: term:WrappedExpression | term:NotExpression | term:ArrayLiteral | term:Term
WrappedExpression: '(' < Expression > ')'
NotExpression: (/ ! | not\s+ /) > Expression
Disjunction: lft:Conjunction (< / \|\| | or\s+ / > rgt:Conjunction)*
Conjunction: lft:Comparison (< / && | and\s+ / > rgt:Comparison)*
Comparison: lft:SumCalculation (< comp:/ == | <= | >= | < | > / > rgt:SumCalculation)?
SumCalculation: lft:ProdCalculation (< op:/ \+ | \- / > rgt:ProdCalculation)*
ProdCalculation: lft:SimpleExpression (< op:/ \/ | \* | % / > rgt:SimpleExpression)*
ArrayLiteral: '[' < Expression? (< ',' > Expression)* > ']'
*/

}
?>
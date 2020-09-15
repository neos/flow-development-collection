<?php
declare(strict_types=1);

namespace Neos\Flow\I18n\Cldr\Reader;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\I18n\Cldr\CldrRepository;
use Neos\Flow\I18n\Locale;

/**
 * A reader for data placed in "plurals" tag in CLDR.
 *
 * There are a few similar words used in plurals.xml file of CLDR used by this
 * class. Following naming convention is used in the code (a name of
 * corresponding tag from xml file is provided in brackets, if any):
 * - ruleset: a set of plural rules for a locale [pluralRules]
 * - rule: a rule for one of the forms: zero, one, two, few, many [pluralRule]
 * - subrule: one of the conditions of rule. One rule can have many conditions
 *   joined with "and" or "or" logical operator.
 *
 * @Flow\Scope("singleton")
 * @see http://www.unicode.org/reports/tr35/#Language_Plural_Rules
 */
class PluralsReader
{
    /**
     * An expression to catch one plural subrule. One rule consists of one or
     * more subrules.
     *
     * @todo improve the regexp pattern
     */
    const PATTERN_MATCH_SUBRULE = '/(n|nmod)([0-9]+)?(is|isnot|in|notin|within|notwithin)([0-9]+)(?:\.\.([0-9]+))?(and|or)?/';

    /**
     * Constants for every plural rule form defined in CLDR.
     */
    const RULE_ZERO = 'zero';
    const RULE_ONE = 'one';
    const RULE_TWO = 'two';
    const RULE_FEW = 'few';
    const RULE_MANY = 'many';
    const RULE_OTHER = 'other';

    /**
     * @var CldrRepository
     */
    protected $cldrRepository;

    /**
     * @var VariableFrontend
     */
    protected $cache;

    /**
     * An array of rulesets, indexed numerically.
     *
     * One ruleset contains one or more rules (at most 5, one for every plural
     * form - zero, one, two, few, many - a rule 'other' is implicit). There can
     * also be NULL ruleset, used by languages which don't have plurals.
     *
     * A rule is an array with following elements:
     * 'modulo' => $x | false,
     * 'condition' => array(0 => 'conditionName', 1 => $x, 2 => $y),
     * 'logicalOperator' => 'and' | 'or' | false
     *
     * Legend:
     * - if 'modulo' key has an integer value, tested variable (call it $n) has
     *   to be replaced with the remainder of division of $n by $x. Otherwise
     *   unchanged $n is used for conditional test.
     * - 'condition' is an indexed array where first element is a name of test
     *   condition (one of: is, isnot, in, notin, within, notwithin). Second
     *   element is a value to compare $n with. Third element is optional, and
     *   is used only for tests where range is needed (last 4 from the list above)
     * - 'logicalOperator' represents a logical operation to be done with next
     *   subrule in chain. If current subrule is a last one (or only one), this
     *   element is set to false.
     *
     * @var array
     */
    protected $rulesets;

    /**
     * An associative array holding information which ruleset is used by given
     * locale. One or more locales can use the same ruleset.
     *
     * @var array
     */
    protected $rulesetsIndices;

    /**
     * @param CldrRepository $repository
     * @return void
     */
    public function injectCldrRepository(CldrRepository $repository)
    {
        $this->cldrRepository = $repository;
    }

    /**
     * Injects the Flow_I18n_Cldr_Reader_PluralsReader cache
     *
     * @param VariableFrontend $cache
     * @return void
     */
    public function injectCache(VariableFrontend $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Constructs the reader, loading parsed data from cache if available.
     *
     * @return void
     * @throws \Neos\Cache\Exception
     */
    public function initializeObject()
    {
        if ($this->cache->has('rulesets') && $this->cache->has('rulesetsIndices')) {
            $this->rulesets = $this->cache->get('rulesets');
            $this->rulesetsIndices = $this->cache->get('rulesetsIndices');
        } else {
            $this->generateRulesets();
            $this->cache->set('rulesets', $this->rulesets);
            $this->cache->set('rulesetsIndices', $this->rulesetsIndices);
        }
    }

    /**
     * Returns matching plural form based on $quantity and $locale provided.
     *
     * Plural form is one of following: zero, one, two, few, many, other.
     * Last one (other) is returned when number provided doesn't match any
     * of the rules, or there is no rules for given locale.
     *
     * @param mixed $quantity A number to find plural form for (float or int)
     * @param Locale $locale
     * @return string One of plural form constants
     */
    public function getPluralForm($quantity, Locale $locale): string
    {
        if (!isset($this->rulesetsIndices[$locale->getLanguage()])) {
            return self::RULE_OTHER;
        }

        $ruleset = $this->rulesets[$locale->getLanguage()][$this->rulesetsIndices[$locale->getLanguage()]] ?? null;

        if ($ruleset === null) {
            return self::RULE_OTHER;
        }

        $subrulePassed = false;

        foreach ($ruleset as $form => $rule) {
            foreach ($rule as $subrule) {
                $subrulePassed = false;

                $processedQuantity = $quantity;

                if ($subrule['modulo'] !== false) {
                    $processedQuantity = fmod($processedQuantity, $subrule['modulo']);
                }

                if ($processedQuantity == floor($processedQuantity)) {
                    $processedQuantity = (int)$processedQuantity;
                }

                $condition = $subrule['condition'];
                switch ($condition[0]) {
                    case 'is':
                    case 'isnot':
                        if (is_int($processedQuantity) && $processedQuantity === $condition[1]) {
                            $subrulePassed = true;
                        }
                        if ($condition[0] === 'isnot') {
                            $subrulePassed = !$subrulePassed;
                        }
                        break;
                    case 'in':
                    case 'notin':
                        if (is_int($processedQuantity) && $processedQuantity >= $condition[1] && $processedQuantity <= $condition[2]) {
                            $subrulePassed = true;
                        }
                        if ($condition[0] === 'notin') {
                            $subrulePassed = !$subrulePassed;
                        }
                        break;
                    case 'within':
                    case 'notwithin':
                        if ($processedQuantity >= $condition[1] && $processedQuantity <= $condition[2]) {
                            $subrulePassed = true;
                        }
                        if ($condition[0] === 'notwithin') {
                            $subrulePassed = !$subrulePassed;
                        }
                        break;
                }

                if (($subrulePassed && $subrule['logicalOperator'] === 'or') || (!$subrulePassed && $subrule['logicalOperator'] === 'and')) {
                    break;
                }
            }

            if ($subrulePassed) {
                return $form;
            }
        }

        return self::RULE_OTHER;
    }

    /**
     * Returns array of plural forms available for particular locale.
     *
     * @param Locale $locale Locale to return plural forms for
     * @return array Plural forms' names (one, zero, two, few, many, other) available for language set in this model
     */
    public function getPluralForms(Locale $locale): array
    {
        if (!isset($this->rulesetsIndices[$locale->getLanguage()])) {
            return [self::RULE_OTHER];
        }

        $ruleset = $this->rulesets[$locale->getLanguage()][$this->rulesetsIndices[$locale->getLanguage()]] ?? null;

        if ($ruleset === null) {
            return [self::RULE_OTHER];
        }

        return array_merge(array_keys($ruleset), [self::RULE_OTHER]);
    }

    /**
     * Generates an internal representation of plural rules which can be found
     * in plurals.xml CLDR file.
     *
     * The properties $rulesets and $rulesetsIndices should be empty before
     * running this method.
     *
     * @return void
     * @throws Exception\InvalidPluralRuleException
     * @see PluralsReader::$rulesets
     */
    protected function generateRulesets(): void
    {
        $model = $this->cldrRepository->getModel('supplemental/plurals');
        $pluralRulesSet = $model->getRawArray('plurals');

        $index = 0;
        foreach ($pluralRulesSet as $pluralRulesNodeString => $pluralRules) {
            $localeLanguages = $model->getAttributeValue($pluralRulesNodeString, 'locales');

            foreach (explode(' ', $localeLanguages) as $localeLanguage) {
                $this->rulesetsIndices[$localeLanguage] = $index;
            }

            if (is_array($pluralRules)) {
                $ruleset = [];
                foreach ($pluralRules as $pluralRuleNodeString => $pluralRule) {
                    $pluralForm = $model->getAttributeValue($pluralRuleNodeString, 'count');
                    $ruleset[$pluralForm] = $this->parseRule($pluralRule);
                }

                foreach (explode(' ', $localeLanguages) as $localeLanguage) {
                    $this->rulesets[$localeLanguage][$index] = $ruleset;
                }
            }

            ++$index;
        }
    }

    /**
     * Parses a plural rule from CLDR.
     *
     * A plural rule in CLDR is a string with one or more test conditions, with
     * 'and' or 'or' logical operators between them. Whole expression can look
     * like this:
     *
     * n is 0 OR n is not 1 AND n mod 100 in 1..19
     *
     * As CLDR documentation says, following test conditions can be used:
     * - is x, is not x: $n is (not) equal $x
     * - in x..y, not in x..y: $n is (not) one of integers from range <$x, $y>
     * - within x..y, not within x..y: $n is (not) any number from range <$x, $y>
     *
     * Where $n can be a number (also float) as is, or a result of $n mod $x.
     *
     * Array returned follows simple internal format (see documentation for
     * $rulesets property for details).
     *
     * @param string $rule
     * @return array Parsed rule
     * @throws Exception\InvalidPluralRuleException When plural rule does not match regexp pattern
     */
    protected function parseRule(string $rule): array
    {
        $parsedRule = [];

        if (preg_match_all(self::PATTERN_MATCH_SUBRULE, strtolower(str_replace(' ', '', $rule)), $matches, \PREG_SET_ORDER)) {
            foreach ($matches as $matchedSubrule) {
                $subrule = [];

                if ($matchedSubrule[1] === 'nmod') {
                    $subrule['modulo'] = (int)$matchedSubrule[2];
                } else {
                    $subrule['modulo'] = false;
                }

                $condition = [$matchedSubrule[3], (int)$matchedSubrule[4]];
                if (!in_array($matchedSubrule[3], ['is', 'isnot'], true)) {
                    $condition[2] = (int)$matchedSubrule[5];
                }

                $subrule['condition'] = $condition;

                if (isset($matchedSubrule[6]) && ($matchedSubrule[6] === 'and' || $matchedSubrule[6] === 'or')) {
                    $subrule['logicalOperator'] = $matchedSubrule[6];
                } else {
                    $subrule['logicalOperator'] = false;
                }

                $parsedRule[] = $subrule;
            }
        } else {
            throw new Exception\InvalidPluralRuleException('A plural rule string is invalid. CLDR files might be corrupted.', 1275493982);
        }

        return $parsedRule;
    }
}

<?php
namespace Neos\FluidAdaptor\ViewHelpers\Widget\Controller;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Utility\ObjectAccess;
use Neos\Utility\Arrays;
use Neos\FluidAdaptor\Core\Widget\AbstractWidgetController;

/**
 * Controller for the auto-complete widget
 */
class AutocompleteController extends AbstractWidgetController
{
    /**
     * @var array
     */
    protected $configuration = array('limit' => 10);

    /**
     * @return void
     */
    protected function initializeAction()
    {
        $this->configuration = Arrays::arrayMergeRecursiveOverrule($this->configuration, $this->widgetConfiguration['configuration'], true);
    }

    /**
     * @return void
     */
    public function indexAction()
    {
        $this->view->assign('id', $this->widgetConfiguration['for']);
    }

    /**
     * @param string $term
     * @return string
     */
    public function autocompleteAction($term)
    {
        $searchProperty = $this->widgetConfiguration['searchProperty'];
        /** @var $queryResult QueryResultInterface */
        $queryResult = $this->widgetConfiguration['objects'];
        $query = clone $queryResult->getQuery();
        $constraint = $query->getConstraint();

        if ($constraint !== null) {
            $query->matching($query->logicalAnd(
                $constraint,
                $query->like($searchProperty, '%' . $term . '%', false)
            ));
        } else {
            $query->matching(
                $query->like($searchProperty, '%' . $term . '%', false)
            );
        }
        if (isset($this->configuration['limit'])) {
            $query->setLimit((integer)$this->configuration['limit']);
        }

        $results = $query->execute();

        $output = array();
        $values = array();
        foreach ($results as $singleResult) {
            $val = ObjectAccess::getPropertyPath($singleResult, $searchProperty);
            if (isset($values[$val])) {
                continue;
            }
            $values[$val] = true;
            $output[] = array(
                'id' => $val,
                'label' => $val,
                'value' => $val
            );
        }
        return json_encode($output);
    }
}

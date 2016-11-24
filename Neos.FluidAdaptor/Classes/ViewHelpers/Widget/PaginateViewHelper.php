<?php
namespace Neos\FluidAdaptor\ViewHelpers\Widget;

/*
 * This file is part of the Neos.FluidAdaptor package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\FluidAdaptor\Core\Widget\AbstractWidgetViewHelper;

/**
 * This ViewHelper renders a Pagination of objects.
 *
 * = Examples =
 *
 * <code title="simple configuration">
 * <f:widget.paginate objects="{blogs}" as="paginatedBlogs" configuration="{itemsPerPage: 5}">
 *   // use {paginatedBlogs} as you used {blogs} before, most certainly inside
 *   // a <f:for> loop.
 * </f:widget.paginate>
 * </code>
 *
 * <code title="full configuration">
 * <f:widget.paginate objects="{blogs}" as="paginatedBlogs" configuration="{itemsPerPage: 5, insertAbove: true, insertBelow: false, maximumNumberOfLinks: 10}">
 *   // This example will display at the maximum 10 links and tries to the settings
 *   // pagesBefore and pagesAfter into account to get the best result
 * </f:widget.paginate>
 * </code>
 * = Performance characteristics =
 *
 * In the above example, it looks like {blogs} contains all Blog objects, thus
 * you might wonder if all objects were fetched from the database.
 * However, the blogs are NOT fetched from the database until you actually use them,
 * so the paginate ViewHelper will adjust the query sent to the database and receive
 * only the small subset of objects.
 * So, there is no negative performance overhead in using the Paginate Widget.
 *
 * @api
 */
class PaginateViewHelper extends AbstractWidgetViewHelper
{
    /**
     * @Flow\Inject
     * @var Controller\PaginateController
     */
    protected $controller;

    /**
     * Render this view helper
     *
     * @param QueryResultInterface $objects
     * @param string $as
     * @param array $configuration
     * @return string
     */
    public function render(QueryResultInterface $objects, $as, array $configuration = array('itemsPerPage' => 10, 'insertAbove' => false, 'insertBelow' => true, 'maximumNumberOfLinks' => 99))
    {
        $response = $this->initiateSubRequest();
        return $response->getContent();
    }
}

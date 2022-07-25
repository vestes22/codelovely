<?php

namespace GoDaddy\WordPress\MWC\Core\Features\CostOfGoods\Events;

use Exception;
use GoDaddy\WordPress\MWC\Common\Content\Context\Screen;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Events\PageViewEvent;

/**
 * WooCommerce Profit Reports page view event class.
 *
 * @since 2.15.0
 */
class ProfitReportsPageViewEvent extends PageViewEvent
{
    /** @var string the report name */
    private $reportName;

    /**
     * ProfitReportsPageViewEvent constructor.
     *
     * @param Screen $screen
     * @param string $reportName
     */
    public function __construct(Screen $screen, string $reportName)
    {
        parent::__construct($screen);

        $this->reportName = $reportName;
    }

    /**
     * Gets the data for the event.
     *
     * @return array
     *
     * @throws Exception
     */
    public function getData() : array
    {
        $data = parent::getData();
        ArrayHelper::set($data, 'name', $this->reportName);

        return $data;
    }
}

<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\events;

use craft\events\CancelableEvent;
use tasdev\orderfulfillments\models\FulfillmentLine;

use craft\commerce\Plugin as Commerce;
use craft\commerce\models\LineItem;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class FulfillableQtyEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var LineItem The line item model.
     */
    public $lineItem;

    /**
     * @var FulfillmentLine[] all the fulfillment lines by the line item id.
     */
    public $fulfillmentItems;

    /**
     * @var int quantity
     */
    public $quantity;

    /**
     * @var boolean to limit the qty retrieved to actual stock
     */
    public $limitToStock;

    /**
     * @var boolean to retrieve the max quantity of an item
     */
    public $getMaxQty;


}

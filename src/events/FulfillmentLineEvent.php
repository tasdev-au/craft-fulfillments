<?php
/**
 * Fulfillments plugin for Craft CMS 5.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\events;

use craft\events\CancelableEvent;
use tasdev\orderfulfillments\models\FulfillmentLine;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class FulfillmentLineEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var FulfillmentLine The fulfillment line model.
     */
    public FulfillmentLine $fulfillmentLine;

    /**
     * @var bool If this is a new fulfillment.
     */
    public bool $isNew = false;
}

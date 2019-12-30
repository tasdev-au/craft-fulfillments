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
use tasdev\orderfulfillments\models\Fulfillment;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class FulfillmentEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var Fulfillment The fulfillment model.
     */
    public $fulfillment;

    /**
     * @var bool If this is a new fulfillment.
     */
    public $isNew = false;
}

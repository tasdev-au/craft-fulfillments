<?php
/**
 * Fulfillments plugin for Craft CMS 4.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\events;

use craft\events\CancelableEvent;
use tasdev\orderfulfillments\models\Carrier;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     4.1.0
 */
class CarrierEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    /**
     * @var Carrier The carrier model.
     */
    public Carrier $carrier;

    /**
     * @var bool If this is a new carrier.
     */
    public bool $isNew = false;
}

<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\base;

use craft\base\Model;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
abstract class TrackingCarrier extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string the display name of the carrier
     */
    public $trackingNumber;

    /**
     * @var int override the default order by specifying an integer
     */
    public $order = 0;


    // Public Methods
    // =========================================================================

    /**
     * Returns the display name of the carrier.
     *
     * @return string
     */
    public abstract function getName(): string;

    /**
     * Returns the tracking URL for the carrier formatted with the tracking number.
     *
     * @return string
     */
    public abstract function getTrackingUrl(): string;
}

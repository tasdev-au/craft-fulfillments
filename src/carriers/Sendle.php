<?php
/**
 * Fulfillments plugin for Craft CMS 4.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */
namespace tasdev\orderfulfillments\carriers;

use Craft;
use tasdev\orderfulfillments\base\TrackingCarrier;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     2.0.0
 * @deprecated 4.1.0
 */
class Sendle extends TrackingCarrier
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the display name of the carrier.
     *
     * @return string
     */
    public function getName(): string
    {
        return Craft::t('order-fulfillments', 'Sendle');
    }

    /**
     * Returns the tracking URL for the carrier formatted with the tracking number.
     *
     * @return string
     */
    public function getTrackingUrl(): string
    {
        return "https://track.sendle.com/tracking?ref={$this->trackingNumber}";
    }
}

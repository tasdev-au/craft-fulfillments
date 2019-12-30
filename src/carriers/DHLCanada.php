<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
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
 * @since     1.0.0
 */
class DHLCanada extends TrackingCarrier
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
        return Craft::t('order-fulfillments', 'DHL Canada');
    }

    /**
     * Returns the tracking URL for the carrier formatted with the tracking number.
     *
     * @return string
     */
    public function getTrackingUrl(): string
    {
        return "http://international.dhl.ca/content/ca/en/express/tracking.shtml?brand=DHL&AWB={$this->trackingNumber}";
    }
}

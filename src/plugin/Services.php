<?php
/**
 * Fulfillments plugin for Craft CMS 4.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\plugin;

use tasdev\orderfulfillments\services\Carriers;
use tasdev\orderfulfillments\services\FulfillmentLines;
use tasdev\orderfulfillments\services\Fulfillments;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
trait Services
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the fulfillments service.
     *
     * @return Fulfillments The fulfillments service
     */
    public function getFulfillments(): Fulfillments
    {
        return $this->get('fulfillments');
    }

    /**
     * Returns the fulfillment lines service.
     *
     * @return FulfillmentLines The fulfillment lines service
     */
    public function getFulfillmentLines(): FulfillmentLines
    {
        return $this->get('fulfillmentLines');
    }

    /**
     * Returns the carriers service.
     *
     * @return Carriers The carriers service
     */
    public function getCarriers(): Carriers
    {
        return $this->get('carriers');
    }


    // Private Methods
    // =========================================================================

    /**
     * Set the components of the plugin
     */
    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'fulfillments' => Fulfillments::class,
            'fulfillmentLines' => FulfillmentLines::class,
            'carriers' => Carriers::class,
        ]);
    }
}

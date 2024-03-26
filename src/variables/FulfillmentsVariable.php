<?php
/**
 * Fulfillments plugin for Craft CMS 5.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\variables;

use tasdev\orderfulfillments\OrderFulfillments;

use Craft;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class FulfillmentsVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Get the plugin instance.
     *
     * @return OrderFulfillments
     */
    public function getPlugin(): OrderFulfillments
    {
        return OrderFulfillments::getInstance();
    }
}

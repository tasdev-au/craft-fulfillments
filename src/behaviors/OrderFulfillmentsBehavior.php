<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\behaviors;


use craft\commerce\elements\Order;
use tasdev\orderfulfillments\models\Fulfillment;
use tasdev\orderfulfillments\OrderFulfillments;
use yii\base\Behavior;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class OrderFulfillmentsBehavior extends Behavior
{
    /**
     * @var ?Fulfillment[]
     */
    private ?array $_fulfillments = null;


    // Public Methods
    // =========================================================================

    /**
     * Returns the fulfillments for the order, or null if the order is not saved.
     *
     * @return ?Fulfillment[]
     */
    public function getFulfillments(): ?array
    {
        if (!$this->_fulfillments) {
            /* @var Order $order */
            $order = $this->owner;

            // Ensure the order is saved.
            if (!$order->id) {
                return null;
            }

            $this->_fulfillments = OrderFulfillments::getInstance()->getFulfillments()->getFulfillmentsByOrder($order);
        }

        return $this->_fulfillments;
    }

    /**
     * Returns the most recent fulfillment for the order,
     * or null if the order is not saved or has no fulfillments.
     *
     * @return ?Fulfillment
     */
    public function getLastFulfillment(): ?Fulfillment
    {
        $fulfillments = $this->getFulfillments();

        return $fulfillments ? $fulfillments[0] : null;
    }
}

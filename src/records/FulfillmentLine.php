<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\records;

use tasdev\orderfulfillments\OrderFulfillments;

use Craft;
use craft\db\ActiveRecord;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 *
 * @property int $id
 * @property int $fulfillmentId
 * @property int $lineItemId
 * @property int $fulfilledQty
 */
class FulfillmentLine extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%orderfulfillments_fulfillment_lines}}';
    }
}

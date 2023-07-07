<?php
/**
 * Fulfillments plugin for Craft CMS 4.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\migrations;

use Craft;
use craft\commerce\models\OrderStatus as OrderStatusModel;
use craft\commerce\Plugin as Commerce;
use craft\db\Migration;
use tasdev\orderfulfillments\models\Carrier;
use tasdev\orderfulfillments\OrderFulfillments;
use yii\base\Exception;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();
        $this->insertDefaultData();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropForeignKeys();
        $this->dropTables();
        $this->dropProjectConfig();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables for Fulfillments
     *
     * @return void
     */
    protected function createTables()
    {
        $this->createTable('{{%orderfulfillments_fulfillments}}', [
            'id' => $this->primaryKey(),
            'orderId' => $this->integer()->notNull(),
            'trackingNumber' => $this->string(),
            'trackingCarrierId' => $this->integer(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%orderfulfillments_fulfillment_lines}}', [
            'id' => $this->primaryKey(),
            'fulfillmentId' => $this->integer()->notNull(),
            'lineItemId' => $this->integer()->notNull(),
            'fulfilledQty' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%orderfulfillments_carriers}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'trackingUrl' => $this->string()->notNull(),
            'isEnabled' => $this->boolean()->defaultValue(true),
            'order' => $this->integer()->defaultValue(999),
            'uid' => $this->uid(),
        ]);
    }

    /**
     * Drop the tables
     *
     * @return void
     */
    protected function dropTables()
    {
        $this->dropTableIfExists('{{%orderfulfillments_fulfillments}}');
        $this->dropTableIfExists('{{%orderfulfillments_fulfillment_lines}}');
        $this->dropTableIfExists('{{%orderfulfillments_carriers}}');

        return null;
    }

    /**
     * Deletes the project config entry.
     */
    public function dropProjectConfig()
    {
        Craft::$app->projectConfig->remove('orderfulfillments');
    }

    /**
     * Insert the default data.
     */
    public function insertDefaultData()
    {
        try {
            $data = [
                'name' => 'Fulfilled',
                'handle' => 'fulfilled',
                'color' => 'yellow',
                'default' => false
            ];
            $orderStatus = new OrderStatusModel($data);
            Commerce::getInstance()->getOrderStatuses()->saveOrderStatus($orderStatus, []);

            $data = [
                'name' => 'Partially Fulfilled',
                'handle' => 'partiallyFulfilled',
                'color' => 'purple',
                'default' => false
            ];
            $orderStatus = new OrderStatusModel($data);
            Commerce::getInstance()->getOrderStatuses()->saveOrderStatus($orderStatus, []);

            $defaultCarriers = [
                [
                    'name' => 'Australia Post',
                    'trackingUrl' => 'https://auspost.com.au/mypost/track/#/details/{trackingNumber}',
                ],
                [
                    'name' => 'DHL Australia',
                    'trackingUrl' => 'https://www.dhl.com/au-en/home/tracking/tracking-freight.html?submit=1&tracking-id={trackingNumber}',
                ],
                [
                    'name' => 'DHL Canada',
                    'trackingUrl' => 'https://www.dhl.com/ca-en/home/tracking/tracking-freight.html?submit=1&tracking-id={trackingNumber}',
                ],
                [
                    'name' => 'DHL Global Mail',
                    'trackingUrl' => 'https://webtrack.dhlglobalmail.com/orders?trackingNumber={trackingNumber}',
                ],
                [
                    'name' => 'DHL US',
                    'trackingUrl' => 'https://www.dhl.com/us-en/home/tracking/tracking-freight.html?submit=1&tracking-id={trackingNumber}',
                ],
                [
                    'name' => 'FedEx',
                    'trackingUrl' => 'https://www.fedex.com/fedextrack/?trknbr={trackingNumber}',
                ],
                [
                    'name' => 'Sendle',
                    'trackingUrl' => 'https://track.sendle.com/tracking?ref={trackingNumber}',
                ],
                [
                    'name' => 'StarTrack',
                    'trackingUrl' => 'https://startrack.com.au/track/details/{trackingNumber}',
                ],
                [
                    'name' => 'UPS',
                    'trackingUrl' => 'https://www.ups.com/track?loc=en_US&tracknum={trackingNumber}',
                ],
                [
                    'name' => 'USPS',
                    'trackingUrl' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels={trackingNumber}',
                ],
            ];

            foreach ($defaultCarriers as $index => $carrier) {
                $carrierModel = new Carrier($carrier);
                $carrierModel->order = $index;

                OrderFulfillments::getInstance()->getCarriers()->saveCarrier($carrierModel);
            }
        } catch (Exception $e) {
            // Already created.
        }
    }

    /**
     * Creates the indexes.
     *
     * @return void
     */
    protected function createIndexes(): void
    {
        $this->createIndex(null, '{{%orderfulfillments_fulfillments}}', 'orderId');
        $this->createIndex(null, '{{%orderfulfillments_fulfillments}}', 'trackingCarrierId');
        $this->createIndex(null, '{{%orderfulfillments_fulfillment_lines}}', 'lineItemId');
        $this->createIndex(null, '{{%orderfulfillments_fulfillment_lines}}', 'fulfillmentId');
        $this->createIndex(null, '{{%orderfulfillments_fulfillment_lines}}', 'fulfillmentId');
    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys(): void
    {
        $this->addForeignKey(null, '{{%orderfulfillments_fulfillments}}', 'orderId', '{{%commerce_orders}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%orderfulfillments_fulfillments}}', 'trackingCarrierId', '{{%orderfulfillments_carriers}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%orderfulfillments_fulfillment_lines}}', 'lineItemId', '{{%commerce_lineitems}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey(null, '{{%orderfulfillments_fulfillment_lines}}', 'fulfillmentId', '{{%orderfulfillments_fulfillments}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function dropForeignKeys(): void
    {
        if ($this->db->tableExists('{{%orderfulfillments_fulfillments}}')) {
            $this->dropAllForeignKeysToTable('{{%orderfulfillments_fulfillments}}');
        }

        if ($this->db->tableExists('{{%orderfulfillments_fulfillment_lines}}')) {
            $this->dropAllForeignKeysToTable('{{%orderfulfillments_fulfillment_lines}}');
        }

        if ($this->db->tableExists('{{%orderfulfillments_carriers}}')) {
            $this->dropAllForeignKeysToTable('{{%orderfulfillments_carriers}}');
        }
    }
}

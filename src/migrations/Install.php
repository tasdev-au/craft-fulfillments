<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
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
use craft\helpers\MigrationHelper;
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
            'trackingCarrierClass' => $this->string(),
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
        } catch (Exception $e) {
            // Already created.
        }
    }

    /**
     * Creates the indexes.
     *
     * @return void
     */
    protected function createIndexes()
    {
        $this->createIndex($this->db->getIndexName('{{%orderfulfillments_fulfillments}}', 'orderId', false), '{{%orderfulfillments_fulfillments}}', 'orderId', false);
        $this->createIndex($this->db->getIndexName('{{%orderfulfillments_fulfillment_lines}}', 'lineItemId', false), '{{%orderfulfillments_fulfillment_lines}}', 'lineItemId', false);
        $this->createIndex($this->db->getIndexName('{{%orderfulfillments_fulfillment_lines}}', 'fulfillmentId', false), '{{%orderfulfillments_fulfillment_lines}}', 'fulfillmentId', false);
    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey($this->db->getForeignKeyName('{{%orderfulfillments_fulfillments}}', 'orderId'), '{{%orderfulfillments_fulfillments}}', 'orderId', '{{%commerce_orders}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%orderfulfillments_fulfillment_lines}}', 'lineItemId'), '{{%orderfulfillments_fulfillment_lines}}', 'lineItemId', '{{%commerce_lineitems}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey($this->db->getForeignKeyName('{{%orderfulfillments_fulfillment_lines}}', 'fulfillmentId'), '{{%orderfulfillments_fulfillment_lines}}', 'fulfillmentId', '{{%orderfulfillments_fulfillments}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * Adds the foreign keys.
     *
     * @return void
     */
    protected function dropForeignKeys()
    {
        if ($this->db->tableExists('{{%orderfulfillments_fulfillments}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%orderfulfillments_fulfillments}}', $this);
        }

        if ($this->db->tableExists('{{%orderfulfillments_fulfillment_lines}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%orderfulfillments_fulfillment_lines}}', $this);
        }
    }
}

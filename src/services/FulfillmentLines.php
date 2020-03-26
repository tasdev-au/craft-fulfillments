<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\services;

use Craft;
use craft\commerce\elements\Order;
use craft\db\Query;
use craft\base\Component;
use craft\commerce\Plugin as Commerce;
use craft\commerce\models\LineItem;
use tasdev\orderfulfillments\events\FulfillmentLineEvent;
use tasdev\orderfulfillments\events\FulfillableQtyEvent;
use tasdev\orderfulfillments\models\Fulfillment;
use tasdev\orderfulfillments\models\FulfillmentLine;
use tasdev\orderfulfillments\records\FulfillmentLine as FulfillmentLineRecord;
use Throwable;
use yii\base\Exception;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class FulfillmentLines extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event FulfillmentLineEvent The event that is raised before a fulfillment line is saved.
     *
     * Plugins can get notified before a fulfillment line is being saved
     *
     * ```php
     * use tasdev\orderfulfillments\events\FulfillmentLineEvent;
     * use tasdev\orderfulfillments\services\FulfillmentLines;
     * use yii\base\Event;
     *
     * Event::on(FulfillmentLines::class, FulfillmentLines::EVENT_BEFORE_SAVE_FULFILLMENT_LINE, function(FulfillmentLineEvent $e) {
     *     // Do something
     * });
     * ```
     */
    const EVENT_BEFORE_SAVE_FULFILLMENT_LINE = 'beforeSaveFulfillmentLine';

    /**
     * @event FulfillmentLineEvent The event that is raised after a fulfillment line is saved.
     *
     * Plugins can get notified after a fulfillment line is being saved
     *
     * ```php
     * use tasdev\orderfulfillments\events\FulfillmentLineEvent;
     * use tasdev\orderfulfillments\services\FulfillmentLines;
     * use yii\base\Event;
     *
     * Event::on(FulfillmentLines::class, FulfillmentLines::EVENT_AFTER_SAVE_FULFILLMENT_LINE, function(FulfillmentLineEvent $e) {
     *     // Do something
     * });
     * ```
     */
    const EVENT_AFTER_SAVE_FULFILLMENT_LINE = 'afterSaveFulfillmentLine';

    /**
     * @event FulfillmentLineEvent This event is raised when a new fulfillment line is created
     */
    const EVENT_CREATE_FULFILLMENT_LINE = 'createFulfillmentLine';

    /**
     * @event FulfillableQtyEvent This event is raised when the fulfillable qty is requested
     */
    const EVENT_GET_FULFILLABLE_QTY = 'getFulfillableQty';


    // Public Methods
    // =========================================================================

    /**
     * Gets a fulfillment line by its ID.
     *
     * @param int $fulfillmentLineId
     * @return FulfillmentLine|null
     */
    public function getFulfillmentLineById($fulfillmentLineId)
    {
        $result = $this->_createFulfillmentLineQuery()
            ->where(['id' => $fulfillmentLineId])
            ->one();

        return $result ? new FulfillmentLine($result) : null;
    }

    /**
     * Gets all fulfillment lines by the fulfillment ID.
     *
     * @param $fulfillmentId
     * @return FulfillmentLine[]
     */
    public function getFulfillmentLinesByFulfillmentId($fulfillmentId)
    {
        $results = $this->_createFulfillmentLineQuery()
            ->where(['fulfillmentId' => $fulfillmentId])
            ->all();

        $lines = [];
        foreach ($results as $result) {
            $lines[] = new FulfillmentLine($result);
        }

        return $lines;
    }

    /**
     * Gets all fulfillment lines by the fulfillment.
     *
     * @param Fulfillment $fulfillment
     * @return FulfillmentLine[]
     */
    public function getFulfillmentLinesByFulfillment(Fulfillment $fulfillment)
    {
        return $this->getFulfillmentLinesByFulfillmentId($fulfillment->id);
    }

    /**
     * Gets all fulfillment lines by the line item id.
     *
     * @param int $lineItemId
     * @return FulfillmentLine[]
     */
    public function getFulfillmentLinesByLineItemId($lineItemId)
    {
        $results = $this->_createFulfillmentLineQuery()
            ->where(['lineItemId' => $lineItemId])
            ->all();

        $lines = [];
        foreach ($results as $result) {
            $lines[] = new FulfillmentLine($result);
        }

        return $lines;
    }

    /**
     * Gets all fulfillment lines by the line item.
     *
     * @param LineItem $lineItem
     * @return FulfillmentLine[]
     */
    public function getFulfillmentLinesByLineItem($lineItem)
    {
        return $this->getFulfillmentLinesByLineItemId($lineItem->id);
    }

    /**
     * Gets the fulfillable quantity for a line item.
     *
     * @param LineItem $lineItem
     * @param Boolean $limitToStock
     * @return int
     */
    public function getFulfillableQty(LineItem $lineItem, $limitToStock = false): int
    {
        $fulfillmentItems = $this->getFulfillmentLinesByLineItem($lineItem);

        $quantity = $lineItem->qty;
        foreach ($fulfillmentItems as $fulfillmentItem) {
            $quantity -= $fulfillmentItem->fulfilledQty;
        }

        $event = new FulfillableQtyEvent([
            'lineItem' => $lineItem,
            'fulfillmentItems' => $fulfillmentItems,
            'quantity' => $quantity,
            'limitToStock' => $limitToStock,
        ]);

        // Raise a 'getFulfillableQty' event
        if ($this->hasEventHandlers(self::EVENT_GET_FULFILLABLE_QTY)) {
            $this->trigger(self::EVENT_GET_FULFILLABLE_QTY, $event);
        }

        return $event->quantity;
    }

    /**
     * Gets unfulfilled line items for an order.
     *
     * @param Order $order
     * @return LineItem[]
     */
    public function getUnfulfilledLineItems($order)
    {
        if (!$order) {
            return [];
        }

        $lineItems = [];

        foreach (Commerce::getInstance()->getLineItems()->getAllLineItemsByOrderId($order->id) as $lineItem) {
            if ($this->getFulfillableQty($lineItem) > 0) {
                $lineItems[] = $lineItem;
            }
        }

        return $lineItems;
    }

    /**
     * Create a fulfillment line.
     *
     * @param LineItem $lineItem The line item.
     *
     * @param int $qty
     * @return FulfillmentLine
     */
    public function createFulfillmentLine(LineItem $lineItem, int $qty): FulfillmentLine
    {
        $fulfillmentLine = new FulfillmentLine();
        $fulfillmentLine->lineItemId = $lineItem->id;
        $fulfillmentLine->fulfilledQty = $qty;

        // Raise a 'createFulfillmentLine' event
        if ($this->hasEventHandlers(self::EVENT_CREATE_FULFILLMENT_LINE)) {
            $this->trigger(self::EVENT_CREATE_FULFILLMENT_LINE, new FulfillmentLineEvent([
                'fulfillmentLine' => $fulfillmentLine,
                'isNew' => true,
            ]));
        }

        return $fulfillmentLine;
    }

    /**
     * Save a fulfillment line.
     *
     * @param FulfillmentLine $fulfillmentLine The fulfillment line to save.
     * @param bool $runValidation Whether the fulfillment line should be validated.
     * @return bool
     * @throws Throwable
     */
    public function saveFulfillmentLine(FulfillmentLine $fulfillmentLine, bool $runValidation = true): bool
    {
        $isNewFulfillmentLine = !$fulfillmentLine->id;

        if ($isNewFulfillmentLine) {
            $fulfillmentLineRecord = new FulfillmentLineRecord();
        } else {
            $fulfillmentLineRecord = FulfillmentLineRecord::findOne($fulfillmentLine->id);

            if (!$fulfillmentLineRecord) {
                throw new Exception(Craft::t('auctions', 'No fulfillment lines exists with the ID “{id}”',
                    ['id' => $fulfillmentLineRecord->id]));
            }
        }

        // Raise a 'beforeSaveFulfillmentItem' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FULFILLMENT_LINE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_FULFILLMENT_LINE, new FulfillmentLineEvent([
                'fulfillmentLine' => $fulfillmentLine,
                'isNew' => $isNewFulfillmentLine,
            ]));
        }

        if ($runValidation && !$fulfillmentLine->validate()) {
            Craft::info('Fulfillment not saved due to validation error.', __METHOD__);
            return false;
        }

        $fulfillmentLineRecord->fulfillmentId = $fulfillmentLine->fulfillmentId;
        $fulfillmentLineRecord->lineItemId = $fulfillmentLine->lineItemId;
        $fulfillmentLineRecord->fulfilledQty = $fulfillmentLine->fulfilledQty;

        if (!$fulfillmentLine->hasErrors()) {
            $db = Craft::$app->getDb();
            $transaction = $db->beginTransaction();

            try {
                $success = $fulfillmentLineRecord->save(false);

                if ($success) {
                    if ($isNewFulfillmentLine) {
                        $fulfillmentLine->id = $fulfillmentLineRecord->id;
                    }

                    $transaction->commit();
                }
            } catch (Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }

            if ($success && $this->hasEventHandlers(self::EVENT_AFTER_SAVE_FULFILLMENT_LINE)) {
                $this->trigger(self::EVENT_AFTER_SAVE_FULFILLMENT_LINE, new FulfillmentLineEvent([
                    'fulfillmentLine' => $fulfillmentLine,
                    'isNew' => $isNewFulfillmentLine,
                ]));
            }

            return $success;
        }

        return false;
    }


    // Private methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving fulfillments.
     *
     * @return Query The query object.
     */
    private function _createFulfillmentLineQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'fulfillmentId',
                'lineItemId',
                'fulfilledQty',
                'uid',
            ])
            ->from(['{{%orderfulfillments_fulfillment_lines}}']);
    }
}

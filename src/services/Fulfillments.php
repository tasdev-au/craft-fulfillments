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
use craft\commerce\Plugin as Commerce;
use craft\db\Query;
use craft\base\Component;
use craft\commerce\elements\Order;
use tasdev\orderfulfillments\OrderFulfillments;
use tasdev\orderfulfillments\models\Fulfillment;
use tasdev\orderfulfillments\events\FulfillmentEvent;
use tasdev\orderfulfillments\records\Fulfillment as FulfillmentRecord;
use yii\base\Exception;
use Throwable;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class Fulfillments extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event FulfillmentEvent The event that is raised before a fulfillment is saved.
     *
     * Plugins can get notified before a fulfillment is being saved
     *
     * ```php
     * use tasdev\orderfulfillments\events\FulfillmentEvent;
     * use tasdev\orderfulfillments\services\Fulfillments;
     * use yii\base\Event;
     *
     * Event::on(Fulfillments::class, Fulfillments::EVENT_BEFORE_SAVE_FULFILLMENT, function(FulfillmentEvent $e) {
     *     // Do something
     * });
     * ```
     */
    const EVENT_BEFORE_SAVE_FULFILLMENT = 'beforeSaveFulfillment';

    /**
     * @event FulfillmentEvent The event that is raised after a fulfillment is saved.
     *
     * Plugins can get notified after a fulfillment is being saved
     *
     * ```php
     * use tasdev\orderfulfillments\events\FulfillmentEvent;
     * use tasdev\orderfulfillments\services\Fulfillments;
     * use yii\base\Event;
     *
     * Event::on(Fulfillments::class, Fulfillments::EVENT_AFTER_SAVE_FULFILLMENT, function(FulfillmentEvent $e) {
     *     // Do something
     * });
     * ```
     */
    const EVENT_AFTER_SAVE_FULFILLMENT = 'afterSaveFulfillment';

    /**
     * @event FulfillmentEvent This event is raised when a new fulfillment is created
     */
    const EVENT_CREATE_FULFILLMENT = 'createFulfillment';


    // Public Methods
    // =========================================================================

    /**
     * Gets a fulfillment by its ID.
     *
     * @param int $fulfillmentId
     * @return Fulfillment|null
     */
    public function getFulfillmentById($fulfillmentId)
    {
        $result = $this->_createFulfillmentQuery()
            ->where(['id' => $fulfillmentId])
            ->one();

        return $result ? new Fulfillment($result) : null;
    }

    /**
     * Gets fulfillments by the order ID.
     *
     * @param int $orderId
     * @return Fulfillment[]|null
     */
    public function getFulfillmentsByOrderId($orderId)
    {
        $results = $this->_createFulfillmentQuery()
            ->where(['orderId' => $orderId])
            ->all();

        $fulfillments = [];

        foreach ($results as $result) {
            $fulfillments[] = new Fulfillment($result);
        }

        return $fulfillments;
    }

    /**
     * Gets fulfillments by the order.
     *
     * @param Order $order
     * @return Fulfillment[]|null
     */
    public function getFulfillmentsByOrder(Order $order)
    {
        return $this->getFulfillmentsByOrderId($order->id);
    }

    /**
     * Create a fulfillment.
     *
     * @param int $orderId The ID of the order the fulfillment represents
     *
     * @return Fulfillment
     */
    public function createFulfillment(int $orderId): Fulfillment
    {
        $fulfillment = new Fulfillment();
        $fulfillment->orderId = $orderId;

        // Raise a 'createFulfillment' event
        if ($this->hasEventHandlers(self::EVENT_CREATE_FULFILLMENT)) {
            $this->trigger(self::EVENT_CREATE_FULFILLMENT, new FulfillmentEvent([
                'fulfillment' => $fulfillment,
                'isNew' => true,
            ]));
        }

        return $fulfillment;
    }

    /**
     * Save a fulfillment.
     *
     * @noinspection PhpDocMissingThrowsInspection
     * @param Fulfillment $fulfillment The fulfillment to save.
     * @param bool $runValidation Whether the fulfillment should be validated.
     * @return bool
     * @throws Exception
     */
    public function saveFulfillment(Fulfillment $fulfillment, bool $runValidation = true): bool
    {
        $isNewFulfillment = !$fulfillment->id;

        if ($isNewFulfillment) {
            $fulfillmentRecord = new FulfillmentRecord();
        } else {
            $fulfillmentRecord = FulfillmentRecord::findOne($fulfillment->id);

            if (!$fulfillmentRecord) {
                throw new Exception(Craft::t('auctions', 'No fulfillments exists with the ID “{id}”',
                    ['id' => $fulfillmentRecord->id]));
            }
        }

        // Raise a 'beforeSaveFulfillment' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_FULFILLMENT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_FULFILLMENT, new FulfillmentEvent([
                'fulfillment' => $fulfillment,
                'isNew' => $isNewFulfillment,
            ]));
        }

        if ($runValidation && !$fulfillment->validate()) {
            Craft::info('Fulfillment not saved due to validation error.', __METHOD__);
            return false;
        }

        $fulfillmentRecord->orderId = $fulfillment->orderId;
        $fulfillmentRecord->trackingNumber = $fulfillment->trackingNumber;
        $fulfillmentRecord->trackingCarrierClass = $fulfillment->trackingCarrierClass;

        if (!$fulfillment->hasErrors()) {
            $db = Craft::$app->getDb();
            $transaction = $db->beginTransaction();

            try {
                $success = $fulfillmentRecord->save(false);

                if ($success) {
                    if ($isNewFulfillment) {
                        $fulfillment->id = $fulfillmentRecord->id;
                    }

                    $transaction->commit();
                }
            } catch (Throwable $e) {
                $transaction->rollBack();
                /** @noinspection PhpUnhandledExceptionInspection */
                throw $e;
            }

            if ($success) {
                foreach ($fulfillment->getFulfillmentLines() as $fulfillmentLine) {
                    if (!!$fulfillmentLine->id || $fulfillmentLine->fulfilledQty > 0) {
                        $fulfillmentLine->fulfillmentId = $fulfillment->id;
                        OrderFulfillments::getInstance()->getFulfillmentLines()->saveFulfillmentLine($fulfillmentLine);
                    }
                }

                // Update order status.
                $order = $fulfillment->getOrder();

                $unfulfilled = OrderFulfillments::getInstance()->getFulfillmentLines()->getUnfulfilledLineItems($order);
                $settings = OrderFulfillments::getInstance()->getSettings();

                if (count($unfulfilled) === 0) {
                    $isPartiallyFulfilled = false;
                    $status = Commerce::getInstance()->getOrderStatuses()->getOrderStatusByHandle($settings->fulfilledStatus);
                } else {
                    $isPartiallyFulfilled = true;
                    $status = Commerce::getInstance()->getOrderStatuses()->getOrderStatusByHandle($settings->partiallyFulfilledStatus);
                }

                $oldStatusId = $order->orderStatusId;

                $order->orderStatusId = $status->id;
                /** @noinspection PhpUnhandledExceptionInspection */
                Craft::$app->getElements()->saveElement($order);

                // Force an order history to be created so a fulfillment email gets sent.
                if (OrderFulfillments::getInstance()->getSettings()->resendPartiallyFulfilledEmail &&
                    $oldStatusId == $status->id &&
                    $isPartiallyFulfilled) {
                    Commerce::getInstance()->getOrderHistories()->createOrderHistoryFromOrder($order, $oldStatusId);
                }
            }

            if ($success && $this->hasEventHandlers(self::EVENT_AFTER_SAVE_FULFILLMENT)) {
                $this->trigger(self::EVENT_AFTER_SAVE_FULFILLMENT, new FulfillmentEvent([
                    'fulfillment' => $fulfillment,
                    'isNew' => $isNewFulfillment,
                ]));
            }

            return $success;
        }

        return false;
    }

    /**
     * Deletes a fulfillment by it's ID.
     *
     * @param int $fulfillmentId
     * @return bool
     */
    public function deleteFulfillmentById(int $fulfillmentId): bool
    {
        $result = (bool)FulfillmentRecord::deleteAll(['id' => $fulfillmentId]);

        return $result;
    }


    // Private methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving fulfillments.
     *
     * @return Query The query object.
     */
    private function _createFulfillmentQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'orderId',
                'trackingNumber',
                'trackingCarrierClass',
                'uid',
            ])
            ->from(['{{%orderfulfillments_fulfillments}}']);
    }
}

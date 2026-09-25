<?php
/**
 * Fulfillments plugin for Craft CMS 5.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\controllers;


use Craft;
use craft\web\Controller;
use craft\commerce\Plugin as Commerce;
use tasdev\orderfulfillments\OrderFulfillments;
use tasdev\orderfulfillments\models\Fulfillment;
use Throwable;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class FulfillmentsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Saves the fulfillment.
     *
     * @throws Throwable
     */
    public function actionSave(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('order-fulfillments-createFulfillments');

        $fulfillment = $this->_buildFulfillmentFromPost();

        if (!OrderFulfillments::getInstance()->getFulfillments()->saveFulfillment($fulfillment)) {
            return $this->asJson([
                'success' => false,
                'error' => $this->_getErrorSummary($fulfillment),
            ]);
        }

        return $this->asJson([
            'success' => true
        ]);
    }

    /**
     * Deletes a fulfillment.
     *
     * @throws Throwable
     */
    public function actionDelete()
    {
        $this->requirePermission('order-fulfillments-deleteFulfillments');
        $this->requirePostRequest();
        $id = Craft::$app->getRequest()->getRequiredBodyParam('id');

        if (OrderFulfillments::getInstance()->getFulfillments()->deleteFulfillmentById($id)) {
            Craft::$app->getSession()->setNotice(Craft::t('order-fulfillments', 'Fulfillment deleted successfully.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('order-fulfillments', 'Couldn’t delete fulfillment.'));
        }

        return $this->redirectToPostedUrl();
    }


    // Private Methods
    // =========================================================================

    /**
     * Joins the fulfillment's errors into one message, naming the item for each line error.
     *
     * @param Fulfillment $fulfillment
     * @return string
     */
    private function _getErrorSummary(Fulfillment $fulfillment): string
    {
        $errors = array_values($fulfillment->getFirstErrors());

        foreach ($fulfillment->getFulfillmentLines() as $fulfillmentLine) {
            foreach ($fulfillmentLine->getFirstErrors() as $error) {
                $errors[] = $fulfillmentLine->getLineItem() ? "$fulfillmentLine: $error" : $error;
            }
        }

        return implode("\n", $errors);
    }

    /**
     * Build a fulfillment from POST data.
     *
     * @return Fulfillment
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     */
    private function _buildFulfillmentFromPost(): Fulfillment
    {
        $fulfillmentLinesService = OrderFulfillments::getInstance()->getFulfillmentLines();
        $request = Craft::$app->getRequest();
        $orderId = $request->getParam('orderId');

        $order = Commerce::getInstance()->getOrders()->getOrderById($orderId);

        if (!$order) {
            throw new BadRequestHttpException(Craft::t('order-fulfillments', 'No order found for ID {orderId}.', [
                'orderId' => $orderId
            ]));
        }

        $fulfillment = OrderFulfillments::getInstance()->getFulfillments()->createFulfillment($orderId);

        $fulfillmentLines = $request->getRequiredBodyParam('fulfillmentLines');
        $fulfillment->trackingNumber = $request->getParam('trackingNumber');
        $fulfillment->trackingCarrierId = $request->getParam('trackingCarrierId');

        foreach ($fulfillmentLines as $lineItemId => $qty) {
            $lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($lineItemId);

            if (!$lineItem) {
                throw new BadRequestHttpException(Craft::t('order-fulfillments', 'No line item found for ID {lineItemId}.', [
                    'lineItemId' => $lineItemId
                ]));
            }

            $fulfillmentLine = $fulfillmentLinesService->createFulfillmentLine($lineItem, intval($qty));
            $fulfillment->addFulfillmentLine($fulfillmentLine);
        }

        return $fulfillment;
    }
}

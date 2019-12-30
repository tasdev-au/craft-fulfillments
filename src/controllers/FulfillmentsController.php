<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\controllers;


use Craft;
use craft\commerce\helpers\Order;
use craft\web\Controller;
use craft\commerce\Plugin as Commerce;
use tasdev\orderfulfillments\OrderFulfillments;
use tasdev\orderfulfillments\models\Fulfillment;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
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
     * Returns the modal form.
     *
     * @return Response|null
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws BadRequestHttpException
     */
    public function actionGetHtml()
    {
        $fulfillment = $this->_buildFulfillmentFromPost();

        return $this->asJson([
            'html' => $this->_getFulfillmentFormHtml($fulfillment),
        ]);
    }

    /**
     * Saves the fulfillment.
     *
     * @throws Throwable
     */
    public function actionSave()
    {
        $fulfillment = $this->_buildFulfillmentFromPost();
        $fulfillment->validate();

        if ($fulfillment->hasErrors() || !OrderFulfillments::getInstance()->getFulfillments()->saveFulfillment($fulfillment, false)) {
            return $this->asJson([
                'success' => false,
                'html' => $this->_getFulfillmentFormHtml($fulfillment),
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
     * Renders the modal form HTML.
     *
     * @param Fulfillment $fulfillment
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    private function _getFulfillmentFormHtml(Fulfillment $fulfillment): string
    {
        $view = Craft::$app->getView();

        $html = $view->renderTemplate('order-fulfillments/_modals/create-fulfillment', [
            'fulfillment' => $fulfillment,
        ]);

        return $html;
    }

    /**
     * Build a fulfillment from POST data.
     *
     * @return Fulfillment
     * @throws BadRequestHttpException
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

        $fulfillmentLines = $request->getParam('fulfillmentLines');
        $fulfillment->trackingNumber = $request->getParam('trackingNumber');
        $fulfillment->trackingCarrierClass = $request->getParam('trackingCarrierClass');

        if ($fulfillmentLines) {
            foreach ($fulfillmentLines as $lineItemId => $qty) {
                $qtyInt = intval($qty);
                $lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($lineItemId);

                $fulfillmentLine = $fulfillmentLinesService->createFulfillmentLine($lineItem, $qtyInt);
                $fulfillment->addFulfillmentLine($fulfillmentLine);
            }
        } else {
            $order = $fulfillment->getOrder();

            foreach ($order->getLineItems() as $lineItem) {
                $fulfillableQty = $fulfillmentLinesService->getFulfillableQty($lineItem);
                $fulfillmentLine = $fulfillmentLinesService->createFulfillmentLine($lineItem, $fulfillableQty);
                $fulfillment->addFulfillmentLine($fulfillmentLine);
            }
        }

        return $fulfillment;
    }
}

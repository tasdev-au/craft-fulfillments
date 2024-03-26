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
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\web\Controller;
use craft\commerce\Plugin as Commerce;
use tasdev\orderfulfillments\OrderFulfillments;
use tasdev\orderfulfillments\models\Settings;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     4.1.0
 */
class PluginController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings(): Response
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        /* @var Settings $settings */
        $settings = OrderFulfillments::getInstance()->getSettings();

        $statuses = Commerce::getInstance()->getOrderStatuses()->getAllOrderStatuses();
        $statusesArray = [null => '---'];
        $statusesArray = array_merge($statusesArray, ArrayHelper::map($statuses, 'handle', 'name'));

        $carriers = OrderFulfillments::getInstance()->getCarriers()->getAllCarriers();

        return $this->renderTemplate('order-fulfillments/settings', [
            'settings' => $settings,
            'statuses' => $statusesArray,
            'carriers' => $carriers,
        ]);
    }

    public function actionCarriers(): Response
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        $carriers = OrderFulfillments::getInstance()->getCarriers()->getAllCarriers();

        return $this->renderTemplate('order-fulfillments/settings/carriers', [
            'carriers' => $carriers,
        ]);
    }

    public function actionEditCarrier(int $carrierId): Response
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        $carrier = OrderFulfillments::getInstance()->getCarriers()->getCarrierById($carrierId);
        $carriers = OrderFulfillments::getInstance()->getCarriers()->getAllCarriers();

        return $this->renderTemplate('order-fulfillments/settings/carriers/_edit', [
            'carrier' => $carrier,
            'carriers' => $carriers,
        ]);
    }

    public function actionSaveCarrier(): ?Response
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $carrierId = $request->getBodyParam('carrierId');

        $carrier = OrderFulfillments::getInstance()->getCarriers()->getCarrierById($carrierId);

        $carrier->name = $request->getBodyParam('name', $carrier->name);
        $carrier->trackingUrl = $request->getBodyParam('trackingUrl', $carrier->trackingUrl);
        $carrier->isEnabled = $request->getBodyParam('isEnabled', $carrier->isEnabled);

        if (!OrderFulfillments::getInstance()->getCarriers()->saveCarrier($carrier)) {
            Craft::$app->getSession()->setError(Craft::t('order-fulfillments', 'Couldn’t save carrier.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'carrier' => $carrier,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('order-fulfillments', 'Carrier saved.'));

        return $this->redirectToPostedUrl();
    }

    public function actionDeleteCarrier(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $carrierId = Craft::$app->getRequest()->getRequiredParam('id');
        OrderFulfillments::getInstance()->getCarriers()->deleteCarrierById($carrierId);

        return $this->asJson(['success' => true]);
    }

    public function actionReorderCarriers(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $ids = Json::decode($this->request->getRequiredBodyParam('ids'));

        if (OrderFulfillments::getInstance()->getCarriers()->reorderCarriers($ids)) {
            return $this->asJson(['success' => true]);
        }

        return $this->asJson(['error' => Craft::t('formie', 'Couldn’t reorder templates.')]);
    }
}

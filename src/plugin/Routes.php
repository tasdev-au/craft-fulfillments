<?php
/**
 * Fulfillments plugin for Craft CMS 4.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\plugin;

use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;

/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
trait Routes
{
    // Private Methods
    // =========================================================================

    /**
     * Control Panel routes.
     *
     * @return void
     */
    public function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'order-fulfillments/settings' => 'order-fulfillments/plugin/settings',
                'order-fulfillments/settings/carriers' => 'order-fulfillments/plugin/carriers',
                'order-fulfillments/settings/carriers/<carrierId:\d+>' => 'order-fulfillments/plugin/edit-carrier',
            ]);
        });
    }
}

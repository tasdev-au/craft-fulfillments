<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments;

use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin as Commerce;
use craft\events\DefineBehaviorsEvent;
use craft\events\PluginEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\services\UserPermissions;
use craft\web\View;
use tasdev\orderfulfillments\behaviors\OrderFulfillmentsBehavior;
use tasdev\orderfulfillments\models\Settings;
use tasdev\orderfulfillments\plugin\Services;
use tasdev\orderfulfillments\plugin\Routes;
use tasdev\orderfulfillments\services\Fulfillments as FulfillmentsService;
use tasdev\orderfulfillments\variables\FulfillmentsVariable;
use tasdev\orderfulfillments\fields\Fulfillments as FulfillmentsField;

use Craft;
use craft\base\Plugin;
use craft\services\Fields;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;

use yii\base\Event;

/**
 * Class Fulfillments
 *
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 *
 * @property  FulfillmentsService $fulfillments
 */
class OrderFulfillments extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var OrderFulfillments
     */
    public static $plugin;


    // Public Properties
    // =========================================================================

    /**
     * @inheritDoc
     */
    public $hasCpSection = false;

    /**a
     * @inheritDoc
     */
    public $hasCpSettings = false;

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';


    // Traits
    // =========================================================================

    use Services;
    use Routes;


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_registerVariable();
        $this->_registerEventHandlers();
        $this->_registerCpRoutes();
        $this->_registerPermissions();
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        return null;
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        $statuses = Commerce::getInstance()->getOrderStatuses()->getAllOrderStatuses();
        $statusesArray = [null => '---'];
        $statusesArray = array_merge($statusesArray, ArrayHelper::map($statuses, 'handle', 'name'));

        return Craft::$app->view->renderTemplate('order-fulfillments/settings', [
            'settings' => $this->getSettings(),
            'statuses' => $statusesArray,
        ]);
    }


    // Private Methods
    // =========================================================================

    /**
     * Register fulfillments template variable.
     */
    private function _registerVariable()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
            /** @var CraftVariable $variable */
            $variable = $event->sender;
            $variable->set('fulfillments', FulfillmentsVariable::class);
        });
    }

    /**
     * Register the event handlers.
     */
    private function _registerEventHandlers()
    {
        if (Craft::$app->getUser()->checkPermission('order-fulfillments-viewFulfillments') ||
            Craft::$app->getUser()->checkPermission('order-fulfillments-createFulfillments')) {
            // Add fulfillments tab to order edit page.
            Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function (TemplateEvent $event) {
                if ($event->template === 'commerce/orders/_edit') {
                    $event->variables['tabs']['order-fulfillments'] = [
                        'label' => 'Fulfillments',
                        'url' => '#fulfillmentsTab',
                        'class' => null,
                    ];
                }
            });

            // Uses order edit template hook to inject order fulfillments.
            Craft::$app->view->hook('cp.commerce.order.content', function (&$context) {
                /* @var Order $order */
                $order = $context['order'];
                $fulfillmentLines = [];

                /* @var LineItem $lineItem */
                foreach ($order->getLineItems() as $lineItem) {
                    $fulfillableQty = $this->getFulfillmentLines()->getFulfillableQty($lineItem, $limitToStock = true);
                    $maxfulfillableQty = $this->getFulfillmentLines()->getFulfillableQty($lineItem);

                    $fulfillmentLines[] = [
                        'id' => $lineItem->id,
                        'title' => $lineItem->description ?? $lineItem->getDescription(),
                        'qty' => $fulfillableQty,
                        'maxQty' => $maxfulfillableQty
                    ];
                }

                $context['fulfillmentLines'] = $fulfillmentLines;

                $carriers = [];
                foreach ($this->getCarriers()->getAllCarriers() as $carrier) {
                    $carriers[$carrier] = (new $carrier)->getName();
                }

                $context['fulfillmentCarriers'] = $carriers;

                return Craft::$app->view->renderTemplate('order-fulfillments/_includes/fulfillments', $context);
            });
        }

        // Add fulfillments behavior to access fulfillments like $order->fulfillments.
        Event::on(Order::class, Order::EVENT_DEFINE_BEHAVIORS, function (DefineBehaviorsEvent $event) {
            $event->behaviors[] = OrderFulfillmentsBehavior::class;
        });

        // Redirect after plugin install
        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN, function (PluginEvent $event) {
            if ($event->plugin === $this) {
                if (Craft::$app->getRequest()->isCpRequest) {
                    Craft::$app->getResponse()->redirect(
                        UrlHelper::cpUrl('settings/plugins/order-fulfillments')
                    )->send();
                }
            }
        });
    }

    /**
     * Register Auction Product permissions
     */
    private function _registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions[Craft::t('order-fulfillments', 'Fulfillments')] = [
                'order-fulfillments-viewFulfillments' => ['label' => Craft::t('order-fulfillments', 'View fulfillments')],
                'order-fulfillments-createFulfillments' => ['label' => Craft::t('order-fulfillments', 'Create fulfillments')],
                'order-fulfillments-deleteFulfillments' => ['label' => Craft::t('order-fulfillments', 'Delete fulfillments')],
            ];
        });
    }
}

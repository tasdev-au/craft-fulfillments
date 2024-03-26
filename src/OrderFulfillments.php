<?php
/**
 * Fulfillments plugin for Craft CMS 5.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments;

use Craft;
use craft\base\Plugin;
use craft\db\Query;
use craft\events\RebuildConfigEvent;
use craft\services\ProjectConfig;
use craft\web\twig\variables\CraftVariable;
use craft\base\Model;
use craft\commerce\elements\Order;
use craft\events\DefineBehaviorsEvent;
use craft\events\PluginEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\TemplateEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\services\UserPermissions;
use craft\web\View;

use tasdev\orderfulfillments\helpers\ProjectConfigData;
use tasdev\orderfulfillments\behaviors\OrderFulfillmentsBehavior;
use tasdev\orderfulfillments\models\Settings;
use tasdev\orderfulfillments\plugin\Services;
use tasdev\orderfulfillments\plugin\Routes;
use tasdev\orderfulfillments\services\Carriers;
use tasdev\orderfulfillments\services\Fulfillments as FulfillmentsService;
use tasdev\orderfulfillments\variables\FulfillmentsVariable;

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
    public static OrderFulfillments $plugin;


    // Public Properties
    // =========================================================================

    /**
     * @inheritDoc
     */
    public bool $hasCpSection = false;

    /**
     * @inheritDoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritDoc
     */
    public string $schemaVersion = '1.0.1';


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
        $this->_registerProjectConfigEventHandlers();
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('order-fulfillments/settings'));
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    /**
     * Register fulfillments template variable.
     */
    private function _registerVariable(): void
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
    private function _registerEventHandlers(): void
    {
        // Add fulfillments tab to order edit page.
        Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function (TemplateEvent $event) {
            if (Craft::$app->getUser()->checkPermission('order-fulfillments-viewFulfillments') ||
                Craft::$app->getUser()->checkPermission('order-fulfillments-createFulfillments')) {
                if ($event->template === 'commerce/orders/_edit') {
                    $event->variables['tabs']['order-fulfillments'] = [
                        'label' => 'Fulfillments',
                        'url' => '#fulfillmentsTab',
                        'class' => null,
                    ];
                }
            }
        });

        // Uses order edit template hook to inject order fulfillments.
        Craft::$app->view->hook('cp.commerce.order.content', function (&$context) {
            if (Craft::$app->getUser()->checkPermission('order-fulfillments-viewFulfillments') ||
                Craft::$app->getUser()->checkPermission('order-fulfillments-createFulfillments')) {
                /* @var Order $order */
                $order = $context['order'];

                $fulfillmentLines = [];

                foreach ($order->getLineItems() as $lineItem) {
                    $fulfillableQty = $this->getFulfillmentLines()->getFulfillableQty($lineItem);
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
                    $carriers[] = [
                        'value' => $carrier->id,
                        'label' => $carrier->name,
                    ];
                }

                $context['fulfillmentCarriers'] = $carriers;
            }

            return Craft::$app->view->renderTemplate('order-fulfillments/_includes/fulfillments', $context);
        });

        // Add fulfillments behavior to access fulfillments like $order->fulfillments.
        Event::on(Order::class, Model::EVENT_DEFINE_BEHAVIORS, function (DefineBehaviorsEvent $event) {
            $event->behaviors[] = OrderFulfillmentsBehavior::class;
        });

        // Redirect after plugin install
        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN, function (PluginEvent $event) {
            if ($event->plugin === $this) {
                if (Craft::$app->getRequest()->isCpRequest) {
                    Craft::$app->getResponse()->redirect(
                        UrlHelper::cpUrl('order-fulfillments/settings')
                    )->send();
                }
            }
        });
    }

    /**
     * Register Auction Product permissions
     */
    private function _registerPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $event->permissions[] = [
                'heading' => Craft::t('order-fulfillments', 'Fulfillments'),
                'permissions' => [
                    'order-fulfillments-viewFulfillments' => ['label' => Craft::t('order-fulfillments', 'View fulfillments')],
                    'order-fulfillments-createFulfillments' => ['label' => Craft::t('order-fulfillments', 'Create fulfillments')],
                    'order-fulfillments-deleteFulfillments' => ['label' => Craft::t('order-fulfillments', 'Delete fulfillments')],
                ],
            ];
        });
    }

    /**
     * Register project config event handlers.
     */
    private function _registerProjectConfigEventHandlers(): void
    {
        $projectConfigService = Craft::$app->getProjectConfig();

        $carrierService = $this->getCarriers();

        $projectConfigService
            ->onAdd(Carriers::CONFIG_CARRIERS_KEY . '.{uid}', [$carrierService, 'handleChangedCarrier'])
            ->onUpdate(Carriers::CONFIG_CARRIERS_KEY . '.{uid}', [$carrierService, 'handleChangedCarrier'])
            ->onRemove(Carriers::CONFIG_CARRIERS_KEY . '.{uid}', [$carrierService, 'handleDeletedCarrier']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function (RebuildConfigEvent $event) {
            $event->config['orderfulfillments'] = ProjectConfigData::rebuildProjectConfig();
        });
    }
}

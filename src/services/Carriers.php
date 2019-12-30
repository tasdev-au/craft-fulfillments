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
use craft\events\RegisterComponentTypesEvent;
use tasdev\orderfulfillments\base\TrackingCarrier;
use tasdev\orderfulfillments\carriers\AusPost;
use tasdev\orderfulfillments\carriers\DHLCanada;
use tasdev\orderfulfillments\carriers\DHLGlobal;
use tasdev\orderfulfillments\carriers\DHLUS;
use tasdev\orderfulfillments\carriers\FedEx;
use tasdev\orderfulfillments\carriers\StarTrack;
use tasdev\orderfulfillments\carriers\UPS;
use tasdev\orderfulfillments\carriers\USPS;
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
class Carriers extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterComponentTypesEvent The event that is triggered when registering carriers.
     *
     * Plugins can register their own carriers.
     *
     * ```php
     * use craft\events\RegisterComponentTypesEvent;
     * use tasdev\orderfulfillments\services\Carriers;
     * use yii\base\Event;
     *
     * Event::on(Carriers::class, Carriers::EVENT_REGISTER_CARRIERS, function(RegisterComponentTypesEvent $e) {
     *     $e->types[] = MyCarrier::class;
     * });
     * ```
     */
    const EVENT_REGISTER_CARRIERS = 'registerCarriers';


    // Public Methods
    // =========================================================================

    /**
     * Returns all registered carriers.
     *
     * @return string[]
     */
    public function getAllCarriers(): array
    {
        $carriers = [
            AusPost::class,
            DHLCanada::class,
            DHLGlobal::class,
            DHLUS::class,
            FedEx::class,
            StarTrack::class,
            UPS::class,
            USPS::class,
        ];

        $event = new RegisterComponentTypesEvent([
            'types' => $carriers
        ]);
        $this->trigger(self::EVENT_REGISTER_CARRIERS, $event);

        usort($event->types, function ($a, $b) {
            $classA = new $a;
            $classB = new $b;

            if ($classA->order !== $classB->order) {
                return $classA->order - $classB->order;
            } else {
                return strcmp($classA->getName(), $classB->getName());
            }
        });

        return $event->types;
    }
}

<?php
/**
 * Fulfillments plugin for Craft CMS 4.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\errors\DeprecationException;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use nystudio107\seomatic\models\jsonld\Car;
use tasdev\orderfulfillments\carriers\AusPost;
use tasdev\orderfulfillments\carriers\DHLCanada;
use tasdev\orderfulfillments\carriers\DHLGlobal;
use tasdev\orderfulfillments\carriers\DHLUS;
use tasdev\orderfulfillments\carriers\FedEx;
use tasdev\orderfulfillments\carriers\Sendle;
use tasdev\orderfulfillments\carriers\StarTrack;
use tasdev\orderfulfillments\carriers\UPS;
use tasdev\orderfulfillments\carriers\USPS;
use tasdev\orderfulfillments\events\CarrierEvent;
use tasdev\orderfulfillments\models\Carrier;
use tasdev\orderfulfillments\records\Carrier as CarrierRecord;
use yii\db\Exception;

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
     * @deprecated 4.1.0
     */
    const EVENT_REGISTER_CARRIERS = 'registerCarriers';

    const CONFIG_CARRIERS_KEY = 'orderfulfillments.carriers';

    /**
     * @event CarrierEvent The event that is triggered before a carrier is saved.
     */
    const EVENT_BEFORE_SAVE_CARRIER = 'beforeSaveCarrier';

    /**
     * @event CarrierEvent The event that is triggered after a carrier is saved.
     */
    const EVENT_AFTER_SAVE_CARRIER = 'afterSaveCarrier';

    /**
     * @var Carrier[]
     */
    private array $_carriersById = [];

    /**
     * @var bool
     */
    private bool $_fetchedAllCarriers = false;


    // Public Methods
    // =========================================================================

    function init(): void
    {
        parent::init();

        if ($this->hasEventHandlers(self::EVENT_REGISTER_CARRIERS)) {
            Craft::$app->getDeprecator()->log('Carriers::EVENT_REGISTER_CARRIERS', 'The `Carriers::EVENT_REGISTER_CARRIERS` event is deprecated. Configure carriers via the Fulfullments plugin settings in the CMS instead.');
        }
    }

    /**
     * Returns all registered carriers.
     *
     * @return Carrier[]
     */
    public function getAllCarriers(): array
    {
        if (!$this->_fetchedAllCarriers) {
            $results = $this->_createCarrierQuery()->all();

            foreach ($results as $result) {
                $this->_memoizeCarrier(new Carrier($result));
            }

            $this->_fetchedAllCarriers = true;
        }

        return $this->_carriersById ?: [];
    }

    /**
     * Get a carrier by id.
     *
     * @param int $carrierId The carrier's id.
     *
     * @return Carrier|null Either the product type or `null`.
     */
    public function getCarrierById(int $carrierId): ?Carrier
    {
        if (isset($this->_carriersById[$carrierId])) {
            return $this->_carriersById[$carrierId];
        }

        if ($this->_fetchedAllCarriers) {
            return null;
        }

        $result = $this->_createCarrierQuery()
            ->where(['id' => $carrierId])
            ->one();

        if (!$result) {
            return null;
        }

        $this->_memoizeCarrier(new Carrier($result));

        return $this->_carriersById[$carrierId];
    }

    /**
     * Save a carrier.
     *
     * @param Carrier $carrier   The carrier model.
     * @param bool    $runValidation If validation should be ran.
     *
     * @return bool Whether the carrier was saved successfully.
     * @throws \Throwable if reasons
     */
    public function saveCarrier(Carrier $carrier, bool $runValidation = true): bool
    {
        if ($runValidation && !$carrier->validate()) {
            Craft::info('Carrier not saved due to validation error.', __METHOD__);

            return false;
        }

        $isNewCarrier = !$carrier->id;

        // Fire a 'beforeSaveProductType' event
        $this->trigger(self::EVENT_BEFORE_SAVE_CARRIER, new CarrierEvent([
            'carrier' => $carrier,
            'isNew' => $isNewCarrier,
        ]));

        if (!$isNewCarrier) {
            $carrierRecord = CarrierRecord::findOne($carrier->id);

            if (!$carrierRecord) {
                throw new Exception("No carrier exists with the ID '{$carrier->id}'");
            }

            $carrierUid = $carrierRecord->uid;
        } else {
            $carrierUid = StringHelper::UUID();
        }

        $projectConfig = Craft::$app->getProjectConfig();

        $configData = [
            'name' => $carrier->name,
            'trackingUrl' => $carrier->trackingUrl,
            'isEnabled' => $carrier->isEnabled,
            'order' => $carrier->order,
        ];

        $configPath = self::CONFIG_CARRIERS_KEY . '.' . $carrierUid;
        $projectConfig->set($configPath, $configData);

        if ($isNewCarrier) {
            $carrier->id = Db::idByUid('{{%orderfulfillments_carriers}}', $carrierUid);
            $carrier->order = (new Query())
                ->select(['id'])
                ->from(['{{%orderfulfillments_carriers}}'])
                ->count();
        }

        $this->_carriersById[$carrier->id] = $carrier;

        // Fire an 'afterSaveProductType' event
        $this->trigger(self::EVENT_AFTER_SAVE_CARRIER, new CarrierEvent([
            'carrier' => $carrier,
            'isNew' => $isNewCarrier,
        ]));

        return true;
    }

    /**
     * Delete a carrier by it's id.
     *
     * @param int $id The carrier's id.
     *
     * @return bool Whether the carrier was deleted successfully.
     * @throws \Throwable if reasons
     */
    public function deleteCarrierById(int $id): bool
    {
        $carrier = $this->getCarrierById($id);

        Craft::$app->getProjectConfig()->remove(self::CONFIG_CARRIERS_KEY . '.' . $carrier->uid);

        return true;
    }

    /**
     * Reorders carriers given the array of ids.
     *
     * @param array $ids
     */
    public function reorderCarriers(array $ids): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $uidsByIds = Db::uidsByIds('{{%orderfulfillments_carriers}}', $ids);

        foreach ($ids as $carrier => $carrierId) {
            if (!empty($uidsByIds[$carrierId])) {
                $carrierUid = $uidsByIds[$carrierId];
                $projectConfig->set(self::CONFIG_CARRIERS_KEY . '.' . $carrierUid . '.order', $carrier);
            }
        }

        return true;
    }

    /**
     * Handle carrier change
     *
     * @param ConfigEvent $event
     * @throws \Throwable
     * @throws Exception
     */
    public function handleChangedCarrier(ConfigEvent $event): void
    {
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $carrierUid = $event->tokenMatches[0];
        $data = $event->newValue;

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $carrierRecord = $this->_getCarrierRecord($carrierUid);

            $carrierRecord->uid = $carrierUid;
            $carrierRecord->name = $data['name'];
            $carrierRecord->trackingUrl = $data['trackingUrl'];
            $carrierRecord->isEnabled = $data['isEnabled'];
            $carrierRecord->order = $data['order'];

            if (isset($data['legacyClass'])) {
                $carrierRecord->legacyClass = $data['legacyClass'];
            }

            // Save the product type
            $carrierRecord->save(false);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * Handle a product type getting deleted
     *
     * @param ConfigEvent $event
     * @throws Exception
     * @throws \Throwable
     */
    public function handleDeletedCarrier(ConfigEvent $event): void
    {

        $carrierUid = $event->tokenMatches[0];
        $carrierRecord = $this->_getCarrierRecord($carrierUid);

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            Craft::$app->getDb()->createCommand()
                ->delete('{{%orderfulfillments_fulfillments}}', ['trackingCarrierId' => $carrierRecord->id])
                ->execute();

            Craft::$app->getDb()->createCommand()
                ->delete('{{%orderfulfillments_carriers}}', ['id' => $carrierRecord->id])
                ->execute();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * Memoize a carrier
     *
     * @param Carrier $carrier The carrier to memoize.
     */
    private function _memoizeCarrier(Carrier $carrier): void
    {
        $this->_carriersById[$carrier->id] = $carrier;
    }

    /**
     * Returns a Query object prepped for retrieving carriers.
     *
     * @return Query The query object.
     */
    private function _createCarrierQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'name',
                'trackingUrl',
                'isEnabled',
                'order',
                'legacyClass',
                'uid'
            ])
            ->orderBy('order')
            ->from(['{{%orderfulfillments_carriers}}']);
    }

    /**
     * Gets a carrier record by uid.
     *
     * @param string $uid
     * @return CarrierRecord
     */
    private function _getCarrierRecord(string $uid): CarrierRecord
    {
        return CarrierRecord::findOne(['uid' => $uid]) ?? new CarrierRecord();
    }
}

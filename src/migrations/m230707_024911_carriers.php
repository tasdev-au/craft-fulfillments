<?php

namespace tasdev\orderfulfillments\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\events\RegisterComponentTypesEvent;
use tasdev\orderfulfillments\models\Carrier;
use tasdev\orderfulfillments\OrderFulfillments;
use tasdev\orderfulfillments\services\Carriers;

/**
 * m230707_024911_carriers migration.
 */
class m230707_024911_carriers extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTable('{{%orderfulfillments_carriers}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'trackingUrl' => $this->string()->notNull(),
            'isEnabled' => $this->boolean()->defaultValue(true),
            'order' => $this->integer()->defaultValue(999),
            'legacyClass' => $this->string(),
            'uid' => $this->uid(),
        ]);

        $this->addColumn('{{%orderfulfillments_fulfillments}}', 'trackingCarrierId', $this->integer());

        $this->createIndex(null, '{{%orderfulfillments_fulfillments}}', 'trackingCarrierId');
        $this->addForeignKey(null, '{{%orderfulfillments_fulfillments}}', 'trackingCarrierId', '{{%orderfulfillments_carriers}}', 'id', 'CASCADE', 'CASCADE');

        $this->_migrateCarriers();

        $this->dropColumn('{{%orderfulfillments_fulfillments}}', 'trackingCarrierClass');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230707_024911_carriers cannot be reverted.\n";
        return false;
    }

    private function _migrateCarriers(): void
    {
        if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $carriers = [
                \tasdev\orderfulfillments\carriers\AusPost::class,
                \tasdev\orderfulfillments\carriers\DHLCanada::class,
                \tasdev\orderfulfillments\carriers\DHLGlobal::class,
                \tasdev\orderfulfillments\carriers\DHLUS::class,
                \tasdev\orderfulfillments\carriers\FedEx::class,
                \tasdev\orderfulfillments\carriers\Sendle::class,
                \tasdev\orderfulfillments\carriers\StarTrack::class,
                \tasdev\orderfulfillments\carriers\UPS::class,
                \tasdev\orderfulfillments\carriers\USPS::class,
            ];

            $event = new RegisterComponentTypesEvent([
                'types' => $carriers
            ]);

            OrderFulfillments::getInstance()->getCarriers()->trigger(Carriers::EVENT_REGISTER_CARRIERS, $event);

            foreach ($event->types as $index => $type) {
                $carrier = new $type();
                $carrier->trackingNumber = '{trackingNumber}';

                $newCarrier = new Carrier([
                    'name' => $carrier->getName(),
                    'trackingUrl' => $carrier->getTrackingUrl(),
                    'isEnabled' => true,
                    'order' => $index,
                ]);

                OrderFulfillments::getInstance()->getCarriers()->saveCarrier($newCarrier);

                $this->update('{{%orderfulfillments_carriers}}', [
                    'legacyClass' => $type
                ], ['id' => $newCarrier->id]);
            }
        }

        $fulfillments = (new Query())
            ->select(['id', 'trackingCarrierClass'])
            ->from('{{%orderfulfillments_fulfillments}}')
            ->all();

        foreach ($fulfillments as $fulfillment) {
            $carrier = (new Query())
                ->select(['id'])
                ->from('{{%orderfulfillments_carriers}}')
                ->where(['legacyClass' => $fulfillment['trackingCarrierClass']])
                ->one();

            if ($carrier) {
                $this->update('{{%orderfulfillments_fulfillments}}', [
                    'trackingCarrierId' => $carrier['id']
                ], ['id' => $fulfillment['id']]);
            }
        }
    }
}

<?php

namespace tasdev\orderfulfillments\migrations;

use Craft;
use craft\db\Migration;

/**
 * m191227_103822_carriers migration.
 */
class m191227_103822_carriers extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->renameColumn('{{%orderfulfillments_fulfillments}}', 'trackingCarrier', 'trackingCarrierClass');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m191227_103822_carriers cannot be reverted.\n";
        return false;
    }
}

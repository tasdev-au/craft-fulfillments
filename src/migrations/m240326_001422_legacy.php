<?php

namespace tasdev\orderfulfillments\migrations;

use Craft;
use craft\db\Migration;

/**
 * m240326_001422_legacy migration.
 */
class m240326_001422_legacy extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        if ($this->db->columnExists('{{%orderfulfillments_carriers}}', 'legacyClass')) {
            $this->dropColumn('{{%orderfulfillments_carriers}}', 'legacyClass');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m240326_001422_legacy cannot be reverted.\n";
        return false;
    }
}
